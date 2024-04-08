<?php

declare(strict_types=1);


namespace Zolinga\Cms\Events;
use Zolinga\System\Events\Event;
use Zolinga\System\Types\OriginEnum;
use DOMDocumentFragment, DOMDocument, DOMElement;

/**
 * Content element event dispatched when a custom content element is found.
 * 
 * This event should be handled to expand the custom content element into HTML.
 * 
 * The element to be processed is passed in the 'input' property.
 * The HTML result of processing the element should be stored in the 'output' DOMDocumentFragment property.
 * 
 * Example:
 * 
 *  if ($event->input->localName === 'my-element') {
 *     $event->output->appendXML('<h1>Hello world!</h1>');
 *  } 
 * 
 * @author Daniel Sevcik <danny@zolinga.net>
 * @date 2024-03-26
 */
class ContentElementEvent extends Event {

    public readonly DOMElement $input;
    public readonly DOMDocumentFragment $output;

    public function __construct(string $type, OriginEnum $origin, DOMElement $input) {
        parent::__construct($type, $origin);

        $this->input = $input;
        $this->output = $this->input->ownerDocument->createDocumentFragment();
    }

}