<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\Cms\Events\ContentElementEvent;
use Zolinga\System\Events\ListenerInterface;

/**
 * Handles <html-to-markdown> tag.
 *
 * Converts HTML content inside the tag to Markdown using XSLT.
 */
class HtmlToMarkdownElement implements ListenerInterface
{
    /**
     * Handle the cms:content:html-to-markdown event.
     *
     * <html-to-markdown>
     * <h1>Heading</h1>
     * <p>This is <strong>bold</strong> and <em>italic</em> text.</p>
     * </html-to-markdown>
     *
     * @param ContentElementEvent $event
     * @return void
     */
    public function onHtmlToMarkdown(ContentElementEvent $event): void
    {
        global $api;

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->substituteEntities = false;
        $doc->strictErrorChecking = false;
        $doc->recover = true;
        $doc->formatOutput = false;
        $doc->resolveExternals = false;
        $doc->validateOnParse = false;
        $doc->loadHTML('<html><body></body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        $body = $doc->getElementsByTagName('body')->item(0);

        foreach ($event->input->childNodes as $child) {
            $body->append($doc->importNode($child, true));
        }

        $markdown = $api->convert->htmlToMarkdown($doc);

        if ($markdown !== null) {
            $pre = $event->output->ownerDocument->createElement('pre');
            $pre->textContent = $markdown;
            $event->output->appendChild($pre);
        }
        $event->setStatus($event::STATUS_OK, 'HTML converted to Markdown.');
    }
}
