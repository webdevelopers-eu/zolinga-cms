<?php

declare(strict_types=1);

namespace Zolinga\Cms\Tree;

/**
 * Represents a single menu item in the tree.
 * 
 * @template TTreeDef as array{title: string, description: string, path: string, urlPath: string, visibility: string, right: string, modified: int, classes: array<string>, children: array<mixed>}
 * 
 * @property-read string $title
 * @property-read string $description
 * @property-read string $path
 * @property-read string $urlPath
 * @property-read string $visibility
 * @property-read string $right
 * @property-read string $classes
 * @property-read array<mixed> $children
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
        if ($this->data['modified'] !== filemtime($this->data['path'])) {
            $api->log->info("zolinga-cms", "Menu cache file {$this->data['path']} has been modified. Flushing cache.");
            $api->cmsTree->flushCache();
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

    public function __toString()
    {
        return "TreeItem[{$this->urlPath}]";
    }
}
