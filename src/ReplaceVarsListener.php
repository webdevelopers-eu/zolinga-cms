<?php
declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\Cms\Events\ContentElementEvent;
use Zolinga\System\Events\ListenerInterface;
use DOMXPath;
use DOMNode;
use DOMText;
use DOMAttr;
use DOMDocument;

/**
 * ReplaceVars content element handler
 * 
 * Replaces variable placeholders in the form {{GET:varname}}, {{POST:varname}},
 * {{GET:varname|defaultValue}}, and {{POST:varname|defaultValue}}
 * in all text nodes and attributes within the element.
 * 
 * @author Daniel Sevcik
 * @date 2025-09-15
 */
class ReplaceVarsListener implements ListenerInterface {

    /**
     * Handle the cms:content:replace-vars event
     * 
     * @param ContentElementEvent $event The event object
     * @return void
     */
    public function onReplaceVars(ContentElementEvent $event): void 
    {
        $doc = new DOMDocument();
        $doc->appendChild($doc->importNode($event->input->cloneNode(true), true));
                
        // Create XPath object for searching
        $xpath = new DOMXPath($doc);
        
        // Find all text nodes within the imported content
        $textNodes = $xpath->query('(.//text()|.//@*)[contains(., "{{")]', $doc->documentElement);
        foreach ($textNodes as $textNode) {
            $textNode->textContent = $this->replace($textNode->textContent);
        }
        
        $imported = $event->output->ownerDocument->importNode($doc->documentElement, true);
        $event->output->append(...iterator_to_array($imported->childNodes));
        $event->setStatus($event::STATUS_OK, "Variables replaced");
    }
    
    /**
     * Process a single node (text or attribute) to replace variables
     * 
        * Syntax: {{METHOD:varname}} or {{METHOD:varname|defaultValue}}
        * METHOD can be GET or POST, and defaultValue is optional.
     *
     * @param string $content The content to process
     * @return string The processed content
     */
    private function replace(string $content): string
    {        
        // Replace {{GET:*}} and {{POST:*}} variables
        $content = preg_replace_callback('/\{\{ (?<method>POST|GET) : (?<varName>[^|}]+) (?:\|(?<defaultValue>[^}]*))? \}\}/x', function($matches) {
            $varName = $matches['varName'];
            $defaultValue = $matches['defaultValue'] ?? '';
            $method = $matches['method'];
            return (!empty(${"_$method"}[$varName]) ? ${"_$method"}[$varName] : $defaultValue) ?? $matches[0];
        }, $content);
        
        return $content;
    }
}
