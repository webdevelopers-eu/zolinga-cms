<?php
declare(strict_types=1);

namespace Zolinga\Cms;

use Zolinga\Cms\Events\ContentElementEvent;
use Zolinga\System\Events\ListenerInterface;

class IncludeFile implements ListenerInterface {

    public function onIncludeFile(ContentElementEvent $event): void 
    {
        global $api;
        
        $basePath = $api->cms->currentPage->layoutFilePath;
        $path = $event->input->getAttribute('src');

        // Todo - to support full localizations then we should use Page::fileToDom()
        // we should change it to some static method.
        $cwd = getcwd();
        chdir(dirname($basePath));
        $realPath = realpath($path);
        chdir($cwd);

        if (!is_file($realPath)) {
            throw new \Exception("File is not a file: $path");
        }

        $doc = $api->cms->currentPage->fileToDom($realPath);
        $event->output->appendChild($event->output->ownerDocument->importNode($doc->documentElement, true));

        $event->setStatus($event::STATUS_OK, "File included: ".basename($path));
    }
}