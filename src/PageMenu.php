<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\Cms\Events\ContentElementEvent;
use Zolinga\System\Events\{RequestResponseEvent, ListenerInterface};
use Zolinga\Cms\Tree\{TreeRoot, TreeItem};
use DOMDocument, Exception, DOMElement;

/**
 * Menu listener that generates the HTML menu structure.
 *
 * @template TTreeDef as array{title: string, description: string, path: string, urlPath: string, visibility: string, right: string, modified: int, classes: array<string>, children: array<mixed>}
 *
 * @author Daniel Sevcik <danny@zolinga.net>
 * @date 2024-03-27
 */
class PageMenu implements ListenerInterface
{
    public function __construct()
    {

    }

    /**
     * Handles the <cms-menu> element.
     * 
     * Syntax:
     * 
     * <cms-menu
     *   [ level="1" ]
     *   [ depth="1" ]
     *   [ id="..." ]
     *   [ class="..."] 
     * ></cms-menu>
     *
     * @param ContentElementEvent $event
     * @return void
     */
    public function onCmsMenu(ContentElementEvent $event): void
    {
        global $api;

        // we need parent pages so we prepend it with $api->cmsTree
        $breadcrumbs = [$api->cmsTree, ...$api->cmsTree->breadcrumbs($api->cms->currentPage->urlPath)];
        $level = (int) $event->input->getAttribute('level') ?: 1;
        $depth = (int) $event->input->getAttribute('depth') ?: 1;
        $startPage = $breadcrumbs[$level - 1] ?? null; // $breadcrumbs is 0-based

        if (!$startPage) {
            $event->setStatus($event::STATUS_NOT_FOUND, "Menu not found.");
            return;
        }
        
        $breadcrumbPaths = array_map(fn($item) => $item->urlPath, $breadcrumbs);
        $menu = $this->generateMenu($event->output->ownerDocument, $startPage, $depth, $breadcrumbPaths); 

        $wrapper = $event->output->ownerDocument->createElement('cms-menu');
        $wrapper->appendChild($menu);
        $wrapper->setAttribute('render', 'client');
        $wrapper->setAttribute('hidden', 'true'); // JS hamburger-menu.js removes it 
        foreach(['id', 'class'] as $attr) {
            if ($event->input->hasAttribute($attr)) {
                $wrapper->setAttribute($attr, $event->input->getAttribute($attr));
            }
        }

        $event->output->appendChild($wrapper);
        $event->setStatus($event::STATUS_OK, "Menu loaded successfully.");
    }

    /**
     * Generate <menu> HTML.
     *
     * @param DOMDocument $doc
     * @param TreeItem<TTreeDef> $item
     * @param integer $depth
     * @param array<string> $breadcrumbPaths
     * @return DOMElement
     */
    private function generateMenu(DOMDocument $doc, TreeItem $item, int $depth, array $breadcrumbPaths): DOMElement {
        $menu = $doc->createElement('menu');

        foreach ($item->children as $child) {
            if ($child->visibility !== 'visible') continue;

            /** @var DOMElement $li */
            $li = $menu->appendChild($doc->createElement('li'));
            $classes = $child->classes;
            if (in_array($child->urlPath, $breadcrumbPaths)) {
                $classes[] = 'active';
            }
            if ($classes) {
                $li->setAttribute('class', implode(' ', $classes));
            }

            /** @var DOMElement $a */
            $a = $li->appendChild($doc->createElement('a'));
            $a->setAttribute('href', $child->canonical ?? $child->urlPath ?? '/');
            $a->textContent = $child->title;

            if ($depth > 1 && $child->children) {
                $li->appendChild($this->generateMenu($doc, $child, $depth - 1, $breadcrumbPaths));
            }
        }

        return $menu;
    }
}
