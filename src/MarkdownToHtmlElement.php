<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\Cms\Events\ContentElementEvent;
use Zolinga\System\Events\ListenerInterface;

/**
 * Handles <markdown-to-html> tag.
 *
 * Converts Markdown content inside the tag to HTML using Parsedown.
 */
class MarkdownToHtmlElement implements ListenerInterface
{
    /**
     * Handle the cms:content:markdown-to-html event.
     *
     * <markdown-to-html>
     * # Heading
     *
     * This is **bold** and *italic* text.
     * </markdown-to-html>
     *
     * @param ContentElementEvent $event
     * @return void
     */
    public function onMarkdownToHtml(ContentElementEvent $event): void
    {
        global $api;

        $markdown = $this->extractMarkdown($event);
        $html = $api->convert->markdownToHtml($markdown);

        $event->output->appendXML($html);
        $event->setStatus($event::STATUS_OK, 'Markdown converted to HTML.');
    }

    /**
     * Extract Markdown text from the element.
     *
     * Preserves indentation and line breaks from the inner content.
     *
     * @param ContentElementEvent $event
     * @return string
     */
    private function extractMarkdown(ContentElementEvent $event): string
    {
        $lines = [];
        foreach ($event->input->childNodes as $node) {
            if ($node->nodeType === XML_TEXT_NODE || $node->nodeType === XML_CDATA_SECTION_NODE) {
                $lines[] = $node->textContent;
            }
        }

        $markdown = implode('', $lines);

        // Strip common leading indentation to allow indented Markdown in HTML
        $markdown = $this->stripCommonIndent($markdown);

        return $markdown;
    }

    /**
     * Remove the smallest common leading whitespace from all non-empty lines.
     *
     * @param string $text
     * @return string
     */
    private function stripCommonIndent(string $text): string
    {
        $lines = explode("\n", $text);
        $indent = null;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            preg_match('/^(\s*)/', $line, $matches);
            $len = strlen($matches[1]);
            if ($indent === null || $len < $indent) {
                $indent = $len;
            }
        }

        if ($indent === null || $indent === 0) {
            return $text;
        }

        $result = [];
        foreach ($lines as $line) {
            $result[] = substr($line, $indent);
        }

        return implode("\n", $result);
    }
}
