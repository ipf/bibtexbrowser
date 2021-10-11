<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser\Utility;

class InternationalizationUtility
{
    public static function translate(string $msg): string
    {
        if (array_key_exists('BIBTEXBROWSER_LANG', $GLOBALS) && array_key_exists($msg, $GLOBALS['BIBTEXBROWSER_LANG']) !== null) {
            return $GLOBALS['BIBTEXBROWSER_LANG'][$msg];
        }

        return $msg;
    }
}
