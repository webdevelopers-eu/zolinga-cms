<?php

declare(strict_types=1);


namespace Zolinga\Cms\Events;
use Zolinga\System\Events\Event;
use Zolinga\System\Types\OriginEnum;
use DOMXPath;
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
 * @property-read DOMElement $input The original custom element to process.
 * @property-read DOMDocumentFragment $output The output fragment to append generated content to.
 * @property-read DOMXPath $inputXPath Cached XPath instance for querying the input document. Pre-registers the "cms" namespace.
 * @property-read DOMXPath $outputXPath Cached XPath instance for querying the output document. Pre-registers the "cms" namespace.
 * 
 * @author Daniel Sevcik <danny@zolinga.net>
 * @date 2024-03-26
 */
class ContentElementEvent extends Event {

    public readonly DOMElement $input;
    public readonly DOMDocumentFragment $output;

    private ?DOMXPath $inputXPathCache = null;
    public DOMXPath $inputXPath {
        get {
            return $this->inputXPathCache ??= $this->createXpath();
        }
    }

    private ?DOMXPath $outputXPathCache = null;
    public DOMXPath $outputXPath {
        get {
            return $this->outputXPathCache ??= $this->createXpath();
        }
    }

    public function __construct(string $type, OriginEnum $origin, DOMElement $input) {
        parent::__construct($type, $origin);

        $this->input = $input;
        $this->output = $this->input->ownerDocument->createDocumentFragment();
    }

    private function createXpath(): DOMXPath
    {
        $xpath = new DOMXPath($this->input->ownerDocument);
        $xpath->registerNamespace("cms", "http://www.zolinga.net/cms");
        return $xpath;
    }

}