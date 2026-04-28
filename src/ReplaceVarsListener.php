<?php
declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\Cms\Events\ContentElementEvent;
use DOMXPath;
use DOMDocument;
use Zolinga\System\Events\ServiceInterface;

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
class ReplaceVarsListener implements ServiceInterface {

    /**
     * List of public vars to be replace in contents using {{VAR:<key>}} syntax.
     * 
     * Any app can set any vars here using $api->replaceVars->set('var', 'value') 
     * and then use <replace-vars>{{VAR:var}}</replace-vars> in the content to replace it with the value.
     *
     * @var array of [key => value]
     */
    private array $vars = [];

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
     * Any application can store content variables by calling $api->replaceVars->set(<key>, <val>)
     * that will be used using {{VAR:<key>}} syntax inside content tag <replace-vars>
     * 
     * Example:
     * $api->replaceVars->set('username', 'Alice');
     * Then in content:
     * <replace-vars>Welcome, {{VAR:username|dear visitor}}!</replace-vars>
     * will output:
     * <replace-vars>Welcome, Alice!</replace-vars>
     */
    public function set(string $key, string $value): void {
        $this->vars[$key] = $value;
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
        // Replace {{VAR:*}}, {{GET:*}} and {{POST:*}} variables
        $content = preg_replace_callback('/\{\{ (?<method>POST|GET|VAR) : (?<varName>[^|}]+) (?:\|(?<defaultValue>[^}]*))? \}\}/x', function ($matches) {
            $varName = trim($matches['varName']);
            $defaultValue = $matches['defaultValue'] ?? null;
            $source = match($matches['method']) {
                'GET' => $_GET,
                'POST' => $_POST,
                'VAR' => $this->vars,
                default => throw new \InvalidArgumentException("Unsupported content variable method for <replace-vars>: {$matches['method']}"),
            };

            if (array_key_exists($varName, $source) && $source[$varName] !== '') {
                $ret = (string) $source[$varName];
            } elseif ($defaultValue !== null) {
                $ret = $defaultValue;
            } else {
                $ret = $matches[0]; // No replacement, keep original
            }

            return $ret;
        }, $content);
        
        return $content;
    }
}
