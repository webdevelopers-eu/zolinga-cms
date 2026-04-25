<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use DOMElement;
use Zolinga\Cms\Events\ContentElementEvent;
use Zolinga\System\Events\ListenerInterface;

/**
 * Handles <random-chooser> tag.
 *
 * Selects matching element nodes using an XPath selector relative to the tag,
 * shuffles the matches, and clones up to the requested number into output.
 */
class RandomChooserListener implements ListenerInterface
{
    /**
     * Handle the cms:content:random-chooser event.
     * 
     * <random-chooser [count="N"] [selector="XPATH"] [text-separator="SEP"]>
     *
     * XPATH defaults to './*' (all child elements). 
     * 
     * Count defaults to 'all' (no limit).
     * 
     * SEP defaults to a empty string. Otherwise it is inserted in between select elements.
     * 
     * Examples:
     * <random-chooser count="3" selector="./article//item">...</random-chooser>
     * <random-chooser count="3" selector="./article//item/text()">...</random-chooser><!-- no elements, just text nodes -->
     * <random-chooser selector="./section">...</random-chooser> <!-- all child sections in random order -->
     * 
     * @param ContentElementEvent $event The content element event.
     * @return void
     */ 
    public function onRandomChooser(ContentElementEvent $event): void
    {
        $selector = $event->input->getAttribute('selector') ?: './*';
        $list = iterator_to_array($event->inputXPath->query($selector, $event->input));
        $itemCountAttr = intval($event->input->getAttribute('count') ?? 'all');
        $itemCount = $itemCountAttr ? min($itemCountAttr, count($list)) : count($list);
        $separator = $event->input->getAttribute('text-separator') ?: '';

        shuffle($list);
        $selected = array_slice($list, 0, $itemCount);

        foreach ($selected as $k => $node) {
            $cloned = $node->cloneNode(true);
            $event->output->append($k ? $separator : '', $event->output->ownerDocument->importNode($cloned, true));
        }
        
        $event->setStatus(
            $event::STATUS_OK,
            sprintf('Selected %d of %d matching nodes.', min($itemCount, count($list)), count($list))
        );
    }
}