<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\Cms\Events\ContentElementEvent;
use DOMAttr, DOMDocument, DOMXPath, DOMNode, DOMElement, Exception, JsonSerializable, Locale, SplFileInfo;

/**
 * Represents a page.
 * 
 * @property-read DOMDocument $doc the content of the page as a DOMDocument. Lazily loaded on first use.
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
    public readonly int $modified;
    private ?string $urlPath = null;
    public readonly ?string $canonical;

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
    private ?DOMDocument $doc = null;

    private readonly DOMDocument $fileDoc; // original file document
    private readonly DOMXPath $fileXPath; // initialized $this->path XPath

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
            ],
            get_meta_tags($path) ?: []
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
        $this->fileDoc = $this->fileToDom($this->path);
        $this->fileXPath = new DOMXPath($this->fileDoc);

        $this->priority = max(0.000001, min(0.999999, (float) ($this->meta['cms_priority'] ?: 0.5)));
        $this->visibility = $this->meta['cms_right'] === 'hidden' ? 'hidden' : 'visible';
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
            dgettext('zolinga-cms', 'Untitled Page');
        $this->modified = filemtime($this->path) ?: 0;
        $this->canonical = $this->fileXPath->evaluate('string(//link[@rel="canonical"]/@href)') ?: null;
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
        if ($this->layoutFilePath) {
            $this->loadTemplate();
            $this->injectContentDocument($this->fileDoc);
        } else {
            $this->doc = $this->fileDoc;
            $this->xpath = $this->fileXPath;
        }

        $this->doc->documentElement->setAttribute('lang', $this->lang ?: 'en');
        $this->processCustomElements();
        $this->reshuffle();
    }

    /**
     * Create a event to process custom elements and dispatch it.
     * @return void
     */
    private function processCustomElements(): void
    {
        global $api;

        // List of registered web components
        $registered = array_map(fn ($atom) => $atom['tag'], $api->manifest['webComponents']);
        $maxDepth = 32;
        do {
            $processed = 0;
            // All element containing a dash are as per WHATWG considered as custom elements.
            $expr = "//*[contains(local-name(), '-')][not(@render = 'client')]"; // [not(self::cms-content)]";

            /** @var array<DOMElement> $elements */
            $elements = iterator_to_array($this->xpath->query($expr) ?: []);

            // $elements = array_filter( // remove elements that are registered Web Components
            //     $elements,
            //     fn (DOMNode $node) => !in_array($node->localName, $registered)
            // );

            // Elements should be in the order of appearance in the document. We start from the most inner elements.
            $elements = array_reverse($elements);

            foreach ($elements as $element) {
                /** @var DOMElement $element */
                $event = new ContentElementEvent(
                    'cms:content:' . $element->localName,
                    ContentElementEvent::ORIGIN_INTERNAL,
                    $element->cloneNode(true) // clone to resist the temptation to modify the original element
                );
                $event->dispatch();
                if ($event->status == $event::STATUS_OK) {
                    $children = iterator_to_array($event->output->childNodes);
                    if ($children) {
                        $element->before(...$children); // append all children of the output
                    }
                    $element->remove();
                    $processed++;
                } else {
                    $element->setAttribute('render', 'client'); // ignore it next time. Events are expensive so do not repeat the attempt.
                }
            }
        } while (--$maxDepth && $processed); // repeat unless there are no elements to process

        if (!$maxDepth) {
            trigger_error("Zolinga CMS: Maximum depth of custom elements reached.", E_USER_WARNING);
        }
    }

    /**
     * Return translated version of the file if it exists.
     *
     * @param string $file
     * @return string the path to the translated file or the original file if no translation exists
     */
    private function getLocalizedFile(string $file): string
    {
        global $api;

        if ($this->lang === false) {
            return $file;
        }

        if ($api->serviceExists('locale')) {
            return $api->locale->getLocalizedFile($file);
        }

        // Fail over if Zolinga Locale module is not installed
        $splFile = new SplFileInfo($file);
        $langFile = $splFile->getPath() . '/' . $splFile->getBasename('.html') . '.' . $this->lang . '.html';

        if (file_exists($langFile)) {
            return $langFile;
        }

        return $file;
    }

    /**
     * Load specified .html template as the main content document into $this->doc
     *
     * @return void
     */
    private function loadTemplate(): void
    {
        // $templatePath = "public://zolinga-cms/designs/{$this->design}/{$this->layout}.html";
        if (!file_exists($this->layoutFilePath)) {
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
    private function fileToDom(string $file): DOMDocument
    {
        $doc = new DOMDocument;
        $html = file_get_contents($this->getLocalizedFile($file))
            or throw new Exception("Failed to read file $file.");

        $html = str_replace(
            ['{{designPath}}', '{{locale}}', '{{lang}}'],
            [$this->designUrlPath, $this->lang ?: 'en-US', Locale::getPrimaryLanguage($this->lang ?: 'en')],
            $html
        );


        $doc->loadHTML($html, LIBXML_NOERROR)
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
            $target = $this->getElementById($attr->value);
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
    private function getElementById(string $id): ?DOMElement
    {
        /** @phpstan-ignore-next-line */
        $el = $this->xpath->query("//*[@id='$id']")->item(0);
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
            'visibility' => $this->visibility,
            'right' => $this->right,
            // 'priority' => $this->priority, already sorted by Page
            'modified' => $this->modified,
            'classes' => $this->classes,
            'children' => $this->__get('children'), // lazy initialization
        ];
    }
}
