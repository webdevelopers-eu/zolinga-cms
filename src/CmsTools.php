<?php

declare(strict_types=1);

namespace Zolinga\Cms;

use SplFileInfo;

/**
 * Helper for resolving localized file paths.
 *
 * Given a file path and a language code, returns the path to the localized
 * version of the file if it exists, otherwise returns the original path.
 *
 * @author Daniel Sevcik <danny@zolinga.net>
 * @date 2026-05-12
 */
class CmsTools
{
    /**
     * Return translated version of the file if it exists.
     *
     * @param string $file absolute path to the file
     * @param false|string $lang language code in 'xx-YY' format, or false to skip negotiation
     * @return string the path to the translated file or the original file if no translation exists
     */
    public static function getLocalizedFile(string $file, false|string $lang): string
    {
        global $api;

        if ($lang === false) {
            return $file;
        }

        if ($api->serviceExists('locale')) {
            return $api->locale->getLocalizedFile($file);
        }

        // Fail over if Zolinga Locale module is not installed
        $splFile = new SplFileInfo($file);
        $langFile = $splFile->getPath() . '/' . $splFile->getBasename('.html') . '.' . $lang . '.html';

        if (file_exists($langFile)) {
            return $langFile;
        }

        return $file;
    }
}