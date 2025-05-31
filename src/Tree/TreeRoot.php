<?php

declare(strict_types=1);

namespace Zolinga\Cms\Tree;

use Zolinga\System\Events\ServiceInterface;
use Zolinga\Cms\Page;

/**
 * Represents the root of the tree.
 * 
 * @template TTreeDef as array{title: string, description: string, path: string, urlPath: string, visibility: string, right: string, modified: int, classes: array<string>, children: array<mixed>}
 * @extends TreeItem<TTreeDef>
 * 
 * 
 * @property-read string $title
 * @property-read string $description
 * @property-read string $path
 * @property-read string $urlPath
 * @property-read string $visibility
 * @property-read string $right
 * @property-read array<mixed> $children
 * 
 * @author Daniel Sevcik <danny@zolinga.net>
 * @data 2024-03-27
 */
class TreeRoot extends TreeItem implements ServiceInterface
{
    public function __construct()
    {
        parent::__construct($this->getData());
    }

    /**
     * Get menu data form cache file. If not available re-generate new cache file.
     *
     * @return TTreeDef The menu data.
     */
    private function getData(): array
    {
        global $api;

        $cacheFile = 'private://zolinga-cms/menu.cache.' . ($api->serviceExists('locale') ? $api->locale->jsLocale . '.' : 'C') . 'json';

        // No file - generate new cache file
        if (!$api->config['cms']['menuCache'] || !is_file($cacheFile)) {
            $rootPage = new Page("private://zolinga-cms/pages/index.html");
            file_put_contents($cacheFile, json_encode($rootPage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
                or throw new \RuntimeException("Zolinga CMS: Failed to write menu cache.");
            $this->warnDisableCache();
        }

        $data = (array) json_decode(file_get_contents($cacheFile) ?: 'false', true)
            or throw new \RuntimeException("Failed to load menu cache.");

        /** @var TTreeDef $data */
        return $data;
    }

    /**
     * Warn the user that menu cache is disabled in random intervals by logging a warning.
     * 
     *
     * @return void
     */
    private function warnDisableCache(): void
    {
        global $api;

        if ($api->config['cms']['menuCache']) {
            return;
        }

        // Do not warn for local development
        if (in_array($_SERVER['REMOTE_ADDR'] ?? 'cli', ['cli', 'localhost', '127.0.0.1'])) {
            return;
        }

        $_SESSION['zolinga-cms'] = $_SESSION['zolinga-cms'] ?? [];
        $_SESSION['zolinga-cms']['menuRefreshCounter'] = ($_SESSION['zolinga-cms']['menuRefreshCounter'] ?? 0) + 1;

        // Not sure if random intervals are the best way to do this, but it's a start.
        if ($_SESSION['zolinga-cms']['menuRefreshCounter'] % 10) { // each 10th time only to don't trash logs too much
            return;
        }

        $api->log->warning("zolinga-cms", "Zolinga CMS: Menu cache is disabled, if the server is live enable it in the configuration file.");
    }

    /**
     * Flush the generated menu structure cache.
     *
     * @return int The number of cache files deleted.
     * @throws \RuntimeException If the cache files cannot be deleted.
     */
    public function flushCache(): int
    {
        global $api;

        $filePath = $api->fs->toPath('private://zolinga-cms/');
        $pattern = "{$filePath}/menu.cache.*.json";
        $files = glob($pattern) ?: [];

        if (empty($files)) {
            $api->log?->info("zolinga-cms", "No menu cache files found to flush: {$pattern}");
            return 0;
        }

        $api->log?->info("zolinga-cms", "Flushing menu cache files: " . implode(", ", $files));
        // Delete all cache files
        foreach ($files as $file) {
            $api->log?->info("zolinga-cms", "Flushing menu cache file: $file");
            unlink($file)
                or throw new \RuntimeException("Zolinga CMS: Failed to delete menu cache file $file.");
        }

        return count($files);
    }
}
