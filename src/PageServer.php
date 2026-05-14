<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use Dom\XPath;
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
    private readonly bool $multilingual;

    public function __construct()
    {
        global $api;

        $this->multilingual = $api->serviceExists('locale');

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
     * @param string $path URL path
     * @return array{status: StatusEnum|null, basePath: string|null, lang: string|null} Status and new path
     */
    private function langRedirect(string $path, string $originalPath): array
    {
        global $api;

        if (!$this->multilingual) {
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
        } else {
            // OK, no redirection needed
            ["lang" => $langRewrite, "path" => $pathRewrite] = $this->parseLangFromPath($path);
            return ["status" => null, "basePath" => $pathRewrite, "lang" => $langRewrite ?: $langOriginal, "redir" => null];
        }

        // If it is a search engine bot (does not indicate lang) and there is no cookie, use 
        // 301 - does not maintain method, 308 keeps POST being a POST.
        // Since requests were probably already processed, we need 301
        if (empty($_COOKIE['lang']) && empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $status = StatusEnum::MOVED_PERMANENTLY;
        } else {
            $status = StatusEnum::TEMPORARY_REDIRECT;
        }

        // Preserve query string (GET parameters) when redirecting
        $query = $_SERVER['QUERY_STRING'] ?? '';
        if ($query !== '') {
            $redir .= '?' . $query;
        }

        // Build full url + $redir path
        header("Location: $redir", true, $status->value);
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
        if ($this->multilingual) {
            return $api->locale->getLocalizedFile($realPath);
        }

        return $realPath;
    }
}
