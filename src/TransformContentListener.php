<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\Cms\Events\ContentElementEvent;
use Zolinga\System\Events\ListenerInterface;
use DOMDocument;
use XSLTProcessor;

/**
 * Handles <transform-content xslt="ZOLINGA_PATH">...</transform-content> tag.
 *
 * Takes inner content, wraps it in a DOMDocument and applies an XSLT stylesheet.
 *
 * Usage:
 *   <transform-content xslt="module://my-module/templates/my-transform.xslt">
 *     <item>Hello</item>
 *   </transform-content>
 *
 * @author Daniel Sevcik
 * @date 2026-03-24
 */
class TransformContentListener implements ListenerInterface
{
    /**
     * Handle the cms:content:transform-content event.
     *
     * @param ContentElementEvent $event The content element event
     * @return void
     */
    public function onTransformContent(ContentElementEvent $event): void
    {
        global $api;

        $xsltPath = $event->input->getAttribute('xslt');
        $stripRoot = match ($event->input->getAttribute('strip-root')) {
            'true' => true,
            'false' => false,
            default => null,
        };

        if (!$xsltPath) {
            $this->appendError($event, 'Missing required attribute "xslt" on &lt;transform-content&gt;.');
            return;
        }

        if (!$xsltPath || !is_file($xsltPath)) {
            $this->appendError($event, 'XSLT template not found: ' . htmlspecialchars($xsltPath, ENT_XML1));
            return;
        }

        // Pre-process inner custom content tags unless disabled
        if ($event->input->getAttribute('preprocess') !== 'false') {
            $api->cmsParser->parse($event->input, true);
        }

        // Build a DOMDocument from the input element (including the element itself)
        $sourceDoc = new DOMDocument();
        // To avoid recursive we create new element to don't copy <transform-content> itself
        $root = $sourceDoc->createElement('void');
        $sourceDoc->appendChild($root);
        foreach ($event->input->childNodes as $child) {
            $root->appendChild($sourceDoc->importNode($child, true));
        }

        // Load the XSLT stylesheet
        $xslDoc = new DOMDocument();
        $loaded = $xslDoc->load($xsltPath, LIBXML_NOENT  | LIBXML_NONET | LIBXML_DTDATTR | LIBXML_NO_XXE | LIBXML_DTDLOAD);
        if (!$loaded) {
            $this->appendError($event, 'Failed to load XSLT template: ' . htmlspecialchars($xsltPath, ENT_XML1));
            return;
        }

        // Apply transformation
        $processor = new XSLTProcessor();

        // Disable PHP functions in XSLT for security
        $processor->registerPHPFunctions([]);

        $imported = @$processor->importStylesheet($xslDoc);
        if (!$imported) {
            $this->appendError($event, 'Failed to import XSLT stylesheet: ' . htmlspecialchars($xsltPath, ENT_XML1));
            return;
        }

        $resultDoc = $processor->transformToDoc($sourceDoc);
        if (!$resultDoc) {
            $this->appendError($event, 'XSLT transformation failed for: ' . htmlspecialchars($xsltPath, ENT_XML1));
            return;
        }

        // Append all child nodes of the result document to the output
        // There is no documentElement is output type is text/plain
        $ownerDoc = $event->output->ownerDocument;
        $strip = $stripRoot ?? $resultDoc?->documentElement?->localName === 'void';
        foreach ($strip ? $resultDoc->documentElement->childNodes : $resultDoc->childNodes as $node) {
            $event->output->appendChild($ownerDoc->importNode($node, true));
        }

        $event->setStatus($event::STATUS_OK, 'XSLT transformation applied: ' . basename($xsltPath));
    }

    /**
     * Append an error message to the output as a visible HTML element.
     *
     * @param ContentElementEvent $event The event
     * @param string $message Error message (pre-escaped for XML)
     * @return void
     */
    private function appendError(ContentElementEvent $event, string $message): void
    {
        $pre = $event->output->ownerDocument->createElement('pre', '');
        $event->output->appendChild($pre); // Ensure output is an element to append to
        $pre->appendChild($event->output->ownerDocument->createTextNode($message));
        $pre->setAttribute('class', 'transform-content-error error');
        $event->setStatus($event::STATUS_ERROR, strip_tags($message));
    }
}
