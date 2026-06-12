<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use Dom\XPath;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Zolinga\System\Events\{ServiceInterface, ContentEvent};
use Zolinga\System\Types\StatusEnum;
use Exception, Locale;

/**
 * Serves the pages.
 * 
 * @property-read Page|null $currentPage The current page.
 * 
 * @author Daniel Sevcik <danny@zolinga.net>
 * @date 2024-03-25
 */
class PageServer implements ServiceInterface
{
    private string $basePath;
    private ?Page $currentPage = null;

    public function __construct()
    {
        global $api;

        $this->basePath = (string) realpath($api->fs->toPath('private://zolinga-cms/pages'))
            or throw new Exception('The private://zolinga-cms/pages directory does not exist.');
    }

    public function __get(string $name): mixed
    {
        switch ($name) {
            case 'currentPage':
                if (!$this->currentPage) {
                    throw new Exception('The current page is not initialized yet. Please wait for the content event "system:content" to be served.', 404);
                }
                return $this->currentPage;
            default:
                throw new Exception("Property $name does not exist.");
        }
    }

    /**
     * Request event listener to ?acme=... POST/GET requests 
     * 
     * @param ContentEvent $event
     * @return void
     */
    public function onContent(ContentEvent $event): void
    {
        global $api;

        if ($event->status !== $event::STATUS_UNDETERMINED) {
            return;
        }

        [
            "status" => $redirStatus,
            "basePath" => $basePath,
            "lang" => $lang,
            "redir" => $redir
        ] = $this->langRedirect($event->path, $event->originalPath);

        if ($redirStatus) { // redirected
            $event->setStatus($redirStatus, "Redirecting from $event->path (original $event->originalPath) to the localized page $redir");
            $event->preventDefault();
            $event->stopPropagation();
            return;
        }

        $event->path = $basePath;

        if ($lang) {
            $api->locale->lang = $lang;
        }

        // Process the page
        $file = $this->findFile('/' . trim($basePath, '/'));
        if (!$file) {
            if (!file_exists('private://zolinga-cms/pages/404.html')) {
                return;
            }
            $file = 'private://zolinga-cms/pages/404.html';
            $event->setStatus($event::STATUS_NOT_FOUND, "Page $basePath not found");
        } else {
            $event->setStatus($event::STATUS_OK, "Page served");
        }

        $this->currentPage = new Page($file);
        $event->content->appendChild($event->content->importNode($this->currentPage->doc->documentElement, true));

        // Remove <meta name="cms.template" ...> tags and <void> elements
        $this->stripTag($event->xpath, '//meta[@name="cms.template"]');
        $this->stripTag($event->xpath, '//void');
        $this->stripTransatorsComments($event->xpath);

        // Add multilingual support if needed
        if ($api->isMultilingual) {
            $this->addAlternateLangLinks($event->xpath, $event->content, $event->originalPath);
        }
    }

    private function addAlternateLangLinks(DOMXPath $xpath, DOMDocument $doc, string $path): void
    {
        global $api;

        $localized = $api->locale->getLocalizedUrls($path);
        $head = $doc->getElementsByTagName('head')->item(0) 
            or throw new \Exception('The page does not have a <head> element.');

        // Check if canonical tag exists, if not, add it. Useful when serving multilingual site's "/" with a default language without redirection.
        if (!$xpath->evaluate('boolean(//link[@rel="canonical"])')) {
            $currentPath = $localized[$api->locale->locale] ?? $path;
            $canonicalEl = $doc->createElement('link');
            $canonicalEl->setAttribute('rel', 'canonical');
            $canonicalEl->setAttribute('href', $api->url->resolveUrl($currentPath));
            $canonicalEl->setAttribute('lang', $api->locale->lang);
            $head->appendChild($canonicalEl);
        }
        
        $count = 0;

        foreach ($localized as $locale => $url) {
            $lang = \Locale::getPrimaryLanguage($locale);
            $url = $api->url->resolveUrl($url);
            $this->createAlternateLangElement($head, $lang, $url);

            if (!$count++) { // default language
                $this->createAlternateLangElement($head, 'x-default', $url);
            }
        }
    }

    private function createAlternateLangElement(DOMElement $head, string $lang, string $url): DOMElement
    {
        $link = $head->ownerDocument->createElement('link');
        $link->setAttribute('rel', 'alternate');
        $link->setAttribute('hreflang', $lang);
        $link->setAttribute('href', $url);
        $link->setAttribute('append-to', 'html-head');
        $head->appendChild($link);
        return $link;
    }


    private function stripTransatorsComments(DOMXPath $xpath): void
    {
        $nodes = $xpath->query('//comment()[starts-with(normalize-space(.), "TRANSLATORS:")]');
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    private function stripTag(DOMXPath $xpath, string $selector): void
    {
        $nodes = $xpath->query($selector);
        foreach ($nodes as $node) {
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Redirect to the localized page if the current page is not localized.
     * 
     * For optimal SEO, redirect only HTTP to HTTPS with a permanent 301 redirect. 
     * Do not automatically redirect visitors from "/" to a language specific URL based on Accept-Language or IP address. 
     * Instead, serve either a default language or a language selection page at "/", and expose localized versions through hreflang tags and sitemap entries. 
     * If the destination varies by user preferences, avoid automatic redirects because they can reduce search engine indexability.
     * 
     * @param string $path URL path
     * @return array{status: StatusEnum|null, basePath: string|null, lang: string|null} Status and new path
     */
    private function langRedirect(string $path, string $originalPath): array
    {
        global $api;

        if (!$api->isMultilingual) {
            return ["status" => null, "basePath" => $path, "lang" => null, "redir" => null];
        }

        $langs = $api->locale->supportedLangs;

        // We take lang from original path because we want to see /en/...
        ["lang" => $langOriginal, "path" => $pathOriginal] = $this->parseLangFromPath($originalPath);

        if (count($langs) == 1 && $langOriginal) {
            // Remove lang
            $redir = $pathOriginal ?: '/';
        } elseif (count($langs) > 1 && !$langOriginal) {
            // Add lang
            $redir = '/' . $api->locale->lang . $originalPath;
            // We don't want to redirect - for SE the <meta> alternative/hreflang should do the job.
            return ["status" => null, "basePath" => $pathOriginal, "lang" => $api->locale->lang, "redir" => null];
        } else {
            // OK, no redirection needed
            ["lang" => $langRewrite, "path" => $pathRewrite] = $this->parseLangFromPath($path);
            return ["status" => null, "basePath" => $pathRewrite, "lang" => $langRewrite ?: $langOriginal, "redir" => null];
        }

        $status = StatusEnum::FOUND;

        // Preserve query string (GET parameters) when redirecting
        $query = $_SERVER['QUERY_STRING'] ?? '';
        if ($query !== '') {
            $redir .= '?' . $query;
        }

        // Build full url + $redir path
        header("Location: $redir", true, $status->value);
        header("Vary: Accept-Language", false);
        return ["status" => $status, "basePath" => null, "lang" => $langOriginal, "redir" => $redir];
    }

    private function parseLangFromPath(string $path): array
    {
        global $api;

        $langs = $api->locale->supportedLangs;
        if (preg_match('/^\/(?<lang>' . implode('|', $langs) . ')(?<path>\/.+)?$/', $path, $match)) {
            return ["lang" => $match['lang'], "path" => $match['path'] ?? ''];
        }
        return ["lang" => null, "path" => $path];
    }

    /**
     * Find a file by given virtual URL path.
     *
     * @param string $urlPath
     * @return string|null
     */
    private function findFile(string $urlPath): ?string
    {
        global $api;

        // Path can be a directory so try index.html first
        if (pathinfo($urlPath, PATHINFO_EXTENSION) !== 'html') {
            $path = $this->findFile($urlPath . "/index.html");
            if ($path) return $path;
        }

        $path = $this->basePath . dirname($urlPath) . '/' . basename($urlPath, '.html') . '.html';
        $realPath = realpath($path);

        // does not exist
        if (!$realPath) {
            return null;
        }

        // Is $dir inside $basePath?
        if (strpos($realPath, $this->basePath) !== 0) {
            throw new Exception('The directory is outside the base path private://zolinga-cms/pages !');
        }

        // For multilingual support we require Zolinga Intl module
        if ($api->isMultilingual) {
            return $api->locale->getLocalizedFile($realPath);
        }

        return $realPath;
    }
}
