<?php
declare(strict_types=1);

namespace Zolinga\Cms;
use Zolinga\System\Events\{Event,WikiRefIntegrationEvent};
use Zolinga\System\Wiki\WikiArticle;
use Zolinga\System\Wiki\WikiText;
use Zolinga\System\Events\ListenerInterface;

/**
 * This class is responsible for generating the Wiki elements that are specific to the Zolinga CMS.
 * 
 * @author Daniel Sevcik <danny@zolinga.net>
 * @date 2024-03-28
 */
class WikiCmsElements extends WikiArticle implements ListenerInterface
{

    public function __construct()
    {
        parent::__construct(":ref:cms", null);
        $this->title = "CMS Elements";

        $list = $this->getWikiList();
        $this->contentFiles[] = new WikiText(<<<EOT
            # Zolinga CMS

            Complete list of currently supported CMS custom elements.

            $list
            EOT, WikiText::MIME_MARKDOWN);
    }

    private function getWikiList(): string
    {
        global $api;

        $listeners = $api->manifest->findByEvent(new Event("cms:content:*", Event::ORIGIN_INTERNAL));
        $list = array_map(function ($listener) {
            return 
            '- [<' . substr($listener['event'], strlen("cms:content:")) . '>](:ref:event:' . $listener['event'] . ")\n" .
            '> ' . $listener['description'];
        }, $listeners);
        sort($list);

        return implode("\n", $list);
    }

    public function onWikiRefDiscovery(WikiRefIntegrationEvent $event): void
    {
        $event->addArticle($this);
        $event->setStatus($event::STATUS_OK, "Added CMS Elements to the Wiki.");
    }

    protected function initChildren(): void
    {
    }
}