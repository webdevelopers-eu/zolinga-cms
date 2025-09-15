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
 * Replaces variable placeholders in the form {{GET:varname}}, {{POST:varname}}
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
     * @param string $content The content to process
     * @return string The processed content
     */
    private function replace(string $content): string
    {        
        // Replace {{GET:*}} variables
        if (isset($_GET) && is_array($_GET) && !empty($_GET)) {
            $content = preg_replace_callback('/\{\{GET:([^}]+)\}\}/', function($matches) {
                $varName = $matches[1];
                return isset($_GET[$varName]) ? $_GET[$varName] : $matches[0];
            }, $content);
        }
        
        // Replace {{POST:*}} variables
        if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
            $content = preg_replace_callback('/\{\{POST:([^}]+)\}\}/', function($matches) {
                $varName = $matches[1];
                return isset($_POST[$varName]) ? $_POST[$varName] : $matches[0];
            }, $content);
        }
        
        return $content;
    }
}
