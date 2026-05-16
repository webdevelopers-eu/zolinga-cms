<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use \DOMAttr, \DOMDocument, \DOMNode, \DOMElement, \Exception, \JsonSerializable;
use \Locale, \DOMXPath;
use const Zolinga\System\ROOT_DIR;


/**
 * Represents a page.
 * 
 * @property-read \DOMDocument $doc the content of the page as a \DOMDocument. Lazily loaded on first use.
 * @property-read array<Page> $children the child pages of the page. Lazily loaded on first use.
 * @property-read string $title the title of the page.
 * @property-read string $description the description of the page.
 * @property-read string $urlPath the URL path of the page.
 * 
 * @author Daniel Sevcik <danny@zolinga.net>
 * @date 2024-03-25
 */
class Page implements JsonSerializable
{
    /**
     * Meta tags from the page. Lowercase with with 
     * non-alphanumeric characters replaced with "_".
     * @var array<string, string> result of get_meta_tags()
     */
    private readonly array $meta;
    public readonly ?string $designUrlPath;
    public readonly ?string $layoutFilePath;
    public readonly string $path;
    public readonly string $localizedPath; // Points to localized version of the master $path if it exists, otherwise to the master $path itself.
    public readonly int $modified;
    private ?string $urlPath = null;
    public readonly ?string $canonical;
    public string $publishedUrlPath {
        get {
            global $api;
            $url = $this->canonical ?? $this->urlPath ?? '/';
            if ($api->isMultilingual && !parse_url($url, PHP_URL_SCHEME)) { // only prepend language if it's a relative URL
                $lang = \Locale::getPrimaryLanguage($this->lang ?: 'en');
                $url = "/$lang$url";
                return $url;
            }
            return $url;
        }
    }

    /**
     * Language of the page. If false then no language negotiation is done.
     *
     * @var false|string $lang in format 'xx-YY' or false if no language negotiation is done.
     */
    public readonly false|string $lang;
    /**
     * The order priority of the page. 0.0 is the lowest, 1.0 is the highest.
     *
     * @var float $priority the priority of the page - float between 0.0000000 and 1.0000000 (exclusive)
     */
    public readonly float $priority;
    private ?\DOMDocument $doc = null;

    private readonly \DOMDocument $fileDoc; // original file document
    private readonly \DOMXPath $fileXPath; // initialized $this->path XPath

    /**
     * Array of child pages.
     *
     * @var array<Page>|null
     */
    private ?array $children = null;
    private DOMXPath $xpath;

    public readonly string $visibility;
    public readonly string|false $right;
    public readonly string $title;
    public readonly string|false $description;

    /**
     * List of CSS classes for the menu item
     *
     * @var array<string> $classes
     */
    public readonly array $classes;

    /**
     * Create a new Page object.
     * 
     * The .html file may contain special metadata.
     *
     * @param string $path the path to the .html file with the content and special metadata.
     * @param string|false|null $lang the language of the page. If null, the $api->locale->jsLocale is used if available. Otherwise no language negotiation is done.
     */
    public function __construct(string $path, string|false|null $lang = null)
    {
        global $api;

        if (is_null($lang) && $api->serviceExists("locale")) {
            $this->lang = $api->locale->jsLocale;
        } elseif (is_string($lang)) {
            $this->lang = Locale::getPrimaryLanguage($lang) . '-' . Locale::getRegion($lang);
        } else {
            $this->lang = false;
        }

        $this->path = $api->fs->toPath($path);
        $this->localizedPath = $api->locale->getLocalizedFile($this->path);


        $this->meta = array_merge(
            [
                'cms_template' => '',
                'cms_priority' => '0.5',
                'cms_title' => '',
                'dc_title' => '',
                'cms_description' => '',
                'dc_description' => '',
                'description' => '',
                'cms_right' => '**not-implemented**',
                'cms_visibility' => 'visible',
                'cms_class' => '', // will be copied to <li class> of the menu
                'cms_canonical' => '', // canonical URL or javascript: scheme
            ],
            get_meta_tags($this->localizedPath) ?: []
        );

        if ($this->meta['cms_template']) {
            if (preg_match('@^(public|dist)://@', $this->meta['cms_template'])) {
                $this->layoutFilePath = $api->fs->toPath($this->meta['cms_template']);
            } else {
                list($design, $layout) = array_map('basename', explode('/', $this->meta['cms_template'] ?: '/'));
                $this->layoutFilePath = $api->fs->toPath("public://zolinga-cms/designs/$design/$layout.html");
            }
            $this->designUrlPath = dirname(parse_url($api->fs->toUrl($this->layoutFilePath), PHP_URL_PATH) ?: '/');
        } else {
            $this->layoutFilePath = null;
            $this->designUrlPath = null;
        }

        // must be after we set $this->design and $this->layout - we replace {{designPath}} in the content
        $this->fileDoc = $this->fileToDom($this->localizedPath);
        $this->fileXPath = new DOMXPath($this->fileDoc);

        $this->priority = max(0.000001, min(0.999999, (float) ($this->meta['cms_priority'] ?: 0.5)));
        $this->visibility = $this->meta['cms_right'] === 'hidden' || $this->meta['cms_visibility'] !== 'visible' ? 'hidden' : 'visible';
        $this->right = $this->meta['cms_right'] ?: false;
        $this->description =
            $this->meta['cms_description'] ?:
            $this->meta['dc_description'] ?:
            $this->meta['description'] ?:
            false;
        $this->classes = trim($this->meta['cms_class'] ?: '') ? (preg_split("/\s+/", $this->meta['cms_class']) ?: []) : [];
        $this->title =
            $this->meta['cms_title'] ?:
            $this->meta['dc_title'] ?:
            $this->fileXPath->evaluate('string(//title)') ?:
            // TRANSLATORS: Default page title used when no title metadata or <title> element is provided.
            dgettext('zolinga-cms', 'Untitled Page');
        $this->modified = filemtime($this->path) ?: 0;
        $this->canonical = $this->meta['cms_canonical'] ?: $this->fileXPath->evaluate('string(//link[@rel="canonical"]/@href)') ?: null;
    }

    public function __get(string $name): mixed
    {
        global $api;

        switch ($name) {
            case 'urlPath':
                /** @phpstan-ignore-next-line */
                if (is_null($this->urlPath)) {
                    $realFile = realpath(basename($this->path) === 'index.html' ? dirname($this->path) : $this->path) ?: '';
                    $realFile = preg_replace('/\.html$/', '', $realFile);
                    $realDir = realpath($api->fs->toPath("private://zolinga-cms/pages")) ?: '';
                    $this->urlPath = str_starts_with($realFile, $realDir) ? (substr($realFile, strlen($realDir)) ?: '/') : false;
                }
                return $this->urlPath;
            case 'children':
                /** @phpstan-ignore-next-line */
                if (is_null($this->children)) {
                    $this->initializeChildren();
                }
                return $this->children;
            case 'doc':
                /** @phpstan-ignore-next-line */
                if (is_null($this->doc)) { // late/lazy initialization
                    $this->initializeContent();
                }
                return $this->doc;
            default:
                throw new Exception("The Page property $name not found.");
        }
    }

    /**
     * Load all child pages.
     * 
     * @return void
     */
    private function initializeChildren(): void
    {
        $this->children = [];
        $dir = dirname($this->path);
        $baseName = basename($this->path, '.html');

        if (is_dir("$dir/$baseName")) {
            $searchDir = "$dir/$baseName";
        } elseif ($baseName === 'index') {
            $searchDir = "$dir";
        } else {
            return;
        }

        $files = array_diff(scandir($searchDir) ?: [], ['.', '..', 'index.html']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (preg_match('/\.[a-z]{2}-[A-Z]{2}\.html$/', $file) && $path !== $this->path) { // translation
                continue;
            } elseif (is_dir($path) && file_exists("$path/index.html")) {
                $this->children[] = new Page("$path/index.html");
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'html') {
                $this->children[] = new Page($path);
            }
        }

        usort($this->children, fn ($a, $b) => $b->priority <=> $a->priority);
    }

    /**
     * Load the .html content file and optionally wrap it into a template
     * specified in metadata 
     * 
     * <meta name="cms.template" content="{design}/{layout}"/>
     * or
     * <meta name="cms.template" content="public://path/to/layout.html"/>
     * 
     * @return void
     */
    private function initializeContent(): void
    {
        global $api;

        if ($this->layoutFilePath) {
            $this->loadTemplate();
            $this->injectContentDocument($this->fileDoc);
        } else {
            $this->doc = $this->fileDoc;
            $this->xpath = $this->fileXPath;
        }

        $this->doc->documentElement->setAttribute('lang', $this->lang ?: 'en');

        // Sticky revision hash that changes anytime zolinga.json changes.
        // So if you want to update it increment any number in any zolinga.json.
        $rev=join("-", array_filter([
            $api->config['version'],
            $api->manifest->revHash
        ]));
        $this->doc->documentElement->setAttribute('data-revision', $rev);

        $this->processCustomElements();
        $this->reshuffle();
        $this->appendRevParam();
    }

    private function appendRevParam(): void
    {
        global $api;

        $rev = $this->doc->documentElement->getAttribute('data-revision');

        // Excluding web-components.js as it is included also from other modules
        // resulting in double-inclusion once with ?rev and once without.
        $expr = <<<XPATH
            (//@src|//link/@href)
                [starts-with(., '/') or starts-with(., '{{')]
                    [not(contains(., '?rev='))]
                        [not(contains(., '/web-components.js'))]
            XPATH;
        $attrs = iterator_to_array($this->xpath->query($expr) ?: []);
        /** @var DOMAttr $attr */
        foreach ($attrs as $attr) {
            $url = $attr->value;
            $path = parse_url($url, PHP_URL_PATH);
            $ext = pathinfo($path, PATHINFO_EXTENSION); // static files have extensions

            if (file_exists(ROOT_DIR . '/public' . $path)) {
                $useRev = filemtime(ROOT_DIR . '/public' . $path);  
            } elseif ($rev && $ext) {
                $useRev = $rev;
            } else {
                continue;
            }

            if (strpos($url, '?') === false) {
                $url .= "?rev=$useRev";
            } else {
                $url .= "&rev=$useRev";
            }

            $attr->value = $url;
        }
    }


    /**
     * Create a event to process custom elements and dispatch it.
     * @return void
     */
    private function processCustomElements(): void
    {
        global $api;
        $api->cmsParser->parse($this->doc->documentElement);
    }

    /**
     * Load specified .html template as the main content document into $this->doc
     *
     * @return void
     */
    private function loadTemplate(): void
    {
        global $api;

        $localizedLayoutPath = $api->locale->getLocalizedFile($this->layoutFilePath);
        if (!file_exists($localizedLayoutPath)) {
            throw new Exception("Template not found: {$this->layoutFilePath}.");
        }

        $this->doc = $this->fileToDom($this->layoutFilePath);
        $this->xpath = new DOMXPath($this->doc);
    }

    /**
     * Get the contents of a file. Also:
     * 
     * - language negotiate the file if translation exists
     * - replace {{designPath}} with the path to the $this->designUrlPath
     *
     * @param string $file
     * @return DOMDocument
     */
    public function fileToDom(string $file): DOMDocument
    {
        global $api;

        $doc = new DOMDocument;
        $html = file_get_contents($api->locale->getLocalizedFile($file))
            or throw new Exception("Failed to read file $file.");

        // HTML-ENTITIES are deprecated in PHP 8.2        
        // $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $html = str_replace(
            ['{{designPath}}', '{{locale}}', '{{lang}}', '{{revHash}}'],
            [$this->designUrlPath, $this->lang ?: 'en-US', Locale::getPrimaryLanguage($this->lang ?: 'en'), $api->manifest->revHash],
            $html
        );

        // Ensure we parse it as HTML with UTF-8 encoding
        if (substr($html, 0, strlen(BOM)) !== BOM) {
            $html = BOM . $html; // prepend BOM if not present to ensure UTF-8 encoding
        }

        $doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NONET | LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)
            or throw new Exception("Failed to parse file $file.");

        return $doc;
    }

    /**
     * Injecect the DOM document into current content by merging
     * <head> sections and appending <body> into current's <cms-content>.
     *
     * @param DOMDocument $content
     * @return void
     */
    private function injectContentDocument(DOMDocument $content): void
    {
        $xpath = new DOMXPath($content);
        $this->merge($xpath, '/html/head/node()', '/html/head');
        $this->merge($xpath, '/html/body/node()', '//cms-content');
    }

    /**
     * Resolve all moving parts:
     * 
     * attribute @append-to
     * attribute @prepend-to
     * attribute @replace
     * attribute @replace-contents
     * attribute @move-before
     * attribute @move-after
     *
     * @return void
     */
    private function reshuffle(): void
    {
        // Move all @append-to elements to the element with that id
        $this->moveAround(
            'append-to',
            'append-contents-to',
            fn (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) => $target->append(...$nodes)
        );

        // Replace all @prepend-to elements with the element with that id
        $this->moveAround(
            'prepend-to',
            'prepend-contents-to',
            fn (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) => $target->prepend(...$nodes)
        );

        // Replace all @replace elements with the element with that id
        $this->moveAround(
            'replace',
            'replace-with-contents',
            // Got DOMException: Not Found Error on PHP 8.2.7
            // fn (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) => $target->replaceWith(...$nodes)
            function (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) {
                $target->before(...$nodes);
                $target->remove();
            }
        );

        // Replace all @replace-contents elements with the element with that id
        $this->moveAround(
            'replace-contents',
            'replace-contents-with-contents',
            // PHP 8.3+: fn (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) => $target->replaceChildren(...$nodes)
            function (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) {
                while ($target->firstChild) {
                    $target->removeChild($target->firstChild);
                }
                $target->append(...$nodes);
            }
        );

        // Move all @move-before elements before the element with that id
        $this->moveAround(
            'move-before',
            'move-contents-before',
            fn (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) => $target->before(...$nodes)
        );

        // Move all @move-after elements after the element with that id
        $this->moveAround(
            'move-after',
            'move-contents-after',
            // Some bug in PHP: $target->after(...$nodes) disappeared the $target
            // fn (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) => $target->after(...$nodes)
            function (DOMElement $target, DOMAttr $attr, DOMNode ...$nodes) {
                // Getting also segfaults on PHP8.2.7 so doing dumb loop 
                foreach (array_reverse($nodes) as $node) {
                    $target->parentNode->insertBefore($node, $target->nextSibling);
                }
            }
        );
    }

    /**
     * Move/append/... the elements around.
     *
     * @param string $attrSelfName the attribute name that says the element itself should be moved 
     * @param string $attrContentsName the attribute name that says the element's children should be moved and an empty element discarded
     * @param callable $moveCallback example: fn (DOMElement $target, DOMNode ...$nodes) => $target->append(...$nodes);
     * @return void
     */
    private function moveAround(string $attrSelfName, string $attrContentsName, callable $moveCallback): void
    {
        /** @var array<DOMAttr> $attrs */
        $attrs = iterator_to_array($this->xpath->query("//@$attrSelfName|//@$attrContentsName") ?: []);
        foreach ($attrs as $attr) {
            $target = $this->getElementBySelector($attr->value);
            if (!$target) continue; // maybe the target is not yet loaded? Leave it as is.

            if ($attr->name === $attrSelfName) {
                $moveCallback($target, $attr, $attr->ownerElement);
                $attr->ownerElement->removeAttribute($attr->name);
            } else {
                $moveCallback($target, $attr, ...iterator_to_array($attr->ownerElement->childNodes));
                $attr->ownerElement->remove();
            }
        }
    }

    /**
     * Merge content from the content document to the target document.
     *
     * @param DOMXPath $contentXPath initalized source document xpath
     * @param string $contentSelector nodes to move
     * @param string $targetSelector where to append the nodes
     * @return void
     */
    private function merge(DOMXPath $contentXPath, string $contentSelector, string $targetSelector): void
    {
        $contentNodes = $contentXPath->query($contentSelector);
        /** @phpstan-ignore-next-line */
        $targetNode = $this->xpath->query($targetSelector)->item(0);

        if (!$targetNode || !$contentNodes) {
            return;
        }

        foreach ($contentNodes as $node) {
            $targetNode->appendChild($this->doc->importNode($node, true));
            if ($node instanceof DOMElement) $node->remove();
        }
    }

    /** 
     * Get an element by its id attribute.
     * 
     * @param string $id
     * @return DOMElement|null
     */
    private function getElementBySelector(string $id): ?DOMElement
    {
        /** @phpstan-ignore-next-line */
        if (str_starts_with($id, 'xpath:')) {
            $xpath = trim(substr($id, 6));
            $el = $this->xpath->query($xpath)->item(0);
            return $el instanceof DOMElement ? $el : null;
        }

        $el = $this->xpath->query("//*[@id='" . ltrim($id, '#') ."']")->item(0);
        return $el instanceof DOMElement ? $el : null;
    }

    

    public function jsonSerialize(): mixed
    {
        global $api;

        return [
            'title' => $this->title,
            'description' => $this->description,
            'path' => $api->fs->toZolingaUri($this->path),
            'urlPath' => $this->__get('urlPath'),
            'canonical' => $this->canonical,
            'publishedUrlPath' => $this->publishedUrlPath,
            'visibility' => $this->visibility,
            'right' => $this->right,
            // 'priority' => $this->priority, already sorted by Page
            'modified' => $this->modified,
            'classes' => $this->classes,
            'children' => $this->__get('children'), // lazy initialization
        ];
    }
}
