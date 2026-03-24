<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\System\Events\{ServiceInterface, ContentEvent};
use Zolinga\System\Types\StatusEnum;
use Exception, Locale;
use DOMElement, DOMDocument, DOMDocumentFragment, DOMXPath;
use Zolinga\Cms\Events\ContentElementEvent;

/**
 * Serves the pages.
 * 
 * @property-read Page|null $currentPage The current page.
 * 
 * @author Daniel Sevcik <danny@zolinga.net>
 * @date 2024-03-25
 */
class ContentParser implements ServiceInterface
{
    /**
     * Create a event to process custom elements and dispatch it.
     * When events returns replace the contents with the output of the event.
     * 
     * Example:
     * 
     * $doc = new DOMDocument();
     * $doc->loadHTML($html);
     * $api->cmsParser->parse($doc->documentElement);
     * 
     * @param DOMElement $content process all custom elements in this content element.
     * @param bool $onlyChildren If true, only the children of the content element will be processed. Otherwise, the content element itself will also be processed.
     * @return void
     */
    public function parse(DOMElement $content, $onlyChildren=false): void {
        $xpath = new DOMXPath($content->ownerDocument);
        $context = $content;

        // List of registered web components
        // $registered = array_map(fn ($atom) => $atom['tag'], $api->manifest['webComponents']);
        $iterations = 512;
        $customElementSelector = "*[contains(local-name(), '-')][not(@render = 'client')]";
        // Find all unparsed parent elements
        $baseCustomElementCount = (int) $xpath->evaluate("number(count(" . ($onlyChildren ? "ancestor-or-self" : "ancestor") . "::{$customElementSelector}))", $context); 
        do {
            $processed = 0;

            // All element containing a dash are as per WHATWG considered as custom elements.
            $expr = ($onlyChildren ? "descendant" : "descendant-or-self") . 
                "::{$customElementSelector}[count(ancestor::{$customElementSelector}) = $baseCustomElementCount]"; // [not(self::cms-content)]";

            /** @var array<DOMElement> $elements */
            $elements = iterator_to_array($xpath->query($expr, $context) ?: []);

            // $elements = array_filter( // remove elements that are not registered Web Components
            //     $elements,
            //     fn (DOMNode $node) => !in_array($node->localName, $registered)
            // );

            // We want the outer elements be processed first.
            // $elements = array_reverse($elements);

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

                    // We have replaced the root node
                    if ($element->isSameNode($context)) {
                        foreach($children as $child) {
                            if ($child->nodeType === XML_ELEMENT_NODE) {
                                $this->parse($child, false);
                            }
                        } 
                        break;
                    }

                    $element->remove();
                } else {
                    $element->setAttribute('render', 'client'); // ignore it next time. Events are expensive so do not repeat the attempt.
                }
                $processed++;
            }
        } while (--$iterations > 0 && $processed); // repeat unless there are no elements to process

        if (!$iterations) {
            trigger_error("Zolinga CMS: Maximum depth of custom elements reached.", E_USER_WARNING);
        }
    }
}