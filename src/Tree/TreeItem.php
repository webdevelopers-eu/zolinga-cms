<?php

declare(strict_types=1);

namespace Zolinga\Cms\Tree;

use Zolinga\Cms\Page;

/**
 * Represents a single menu item in the tree.
 * 
 * @template TTreeDef as array{title: string, description: string, path: string, urlPath: string, visibility: string, right: string, modified: int, classes: array<string>, mcpResources: array<string>, children: array<mixed>}
 * 
 * @property-read string $title the display title of the page
 * @property-read string $description the short description/summary of the page
 * @property-read string $path the filesystem path to the .html page file (e.g. /var/www/.../pages/foo/index.html)
 * @property-read string $urlPath the URL path of the page relative to the site root (e.g. /foo), derived from $path
 * @property-read string $visibility whether the page is shown in menus: 'visible' or 'hidden'
 * @property-read string $right the access right required to view the page, or false if none
 * @property-read string $classes CSS classes for the menu item element
 * @property-read array<mixed> $children the child pages of this menu item
 * 
 * @author Daniel Sevcik <danny@zolinga.net>
 * @data 2024-03-27
 */
class TreeItem
{
    /**
     * Cached data from menu.cache.json file
     *
     * @var TTreeDef $data
     */
    private readonly array $data;

    /** 
     * Lazily initialized children of this node
     * 
     * @var array<TreeItem<TTreeDef>>|null $children
     */
    private ?array $children = null;

    /**
     * Subtree of data as stored in menu.cache.json file
     *
     * @param TTreeDef $data
     */
    public function __construct(array $data)
    {
        global $api;

        $this->data = $data;

        // This can slow down the system, but it is necessary to keep the cache up to date.
        // If there is too many pages and menus have too many records we need to change that. 
        // (this object is created only for displayed menu items)
        $modified = file_exists($this->data['path'] ?? '') ? filemtime($this->data['path']) : 0;
        $previous = $this->data['modified'] ?? 0;
        if (!$modified || $previous !== $modified) {
            $api->log?->info("zolinga-cms", "Menu cache file {$this->data['path']} has been modified on " . date('c', $modified) . " (previous modification " . date('c', $previous) . "). Flushing cache.");
            if (!$api->serviceExists('cmsTree') || !is_int($api->cmsTree?->flushCache())) {
                $api->log?->warning("zolinga-cms", "Failed to flush menu cache. \$api->cmsTree is not available or does not implement flushCache().");
            }
        }
    }

    public function __get(string $name): mixed
    {
        switch ($name) {
            case 'title':
            case 'description':
            case 'path':
            case 'urlPath':
            case 'visibility':
            case 'modified':
            case 'right':
            case 'canonical':
            case 'classes':
            case 'publishedUrlPath':
                return $this->data[$name];
            case 'children':
                if ($this->children === null) {
                    /** @phpstan-ignore-next-line */
                    $this->children = array_map(fn (array $data) => new TreeItem($data), $this->data['children']);
                }
                return $this->children;
            default:
                throw new \InvalidArgumentException("Property $name does not exist.");
        }
    }

    /**
     * Return the list of MenuItem objects that represent the breadcrumb trail to the given page.
     * 
     * Example:
     * 
     * $breadcrumbs = $item->getUrlAxis('/path/to/page');
     * // [TreeItem, TreeItem, TreeItem]
     *
     * @param string $url
     * @return array<TreeItem<TTreeDef>>
     */
    public function breadcrumbs(string $url): array
    {
        $path = trim(parse_url(trim($url, '/'), PHP_URL_PATH) ?: '', '/');

        if ($path === '') {
            return [];
        }

        list($search, $reminder) = [...explode('/', $path, 2), null];

        foreach ($this->__get('children') as $child) {
            if (basename($child->urlPath) === $search) {
                $recursive = $reminder !== null ? $child->breadcrumbs($reminder) : [];
                return [$child, ...$recursive];
            }
        }

        return [];
    }

    /**
     * Recursively walk the subtree rooted at this item and yield all TreeItem nodes.
     *
     * @return \Generator<TreeItem<TTreeDef>>
     */
    public function walkTree(): \Generator
    {
        yield $this;
        foreach ($this->__get('children') as $child) {
            yield from $child->walkTree();
        }
    }

    /**
     * Find a TreeItem in the subtree by its URL path.
     *
     * @param string $urlPath the URL path to search for (e.g. /about)
     * @return TreeItem<TTreeDef>|null
     */
    public function findByPath(string $urlPath): ?TreeItem
    {
        $normalized = '/' . trim($urlPath, '/');

        foreach ($this->walkTree() as $item) {
            if ($item->urlPath === $normalized) {
                return $item;
            }
        }

        return null;
    }

    public function __toString()
    {
        return "TreeItem[{$this->urlPath}]";
    }
}
