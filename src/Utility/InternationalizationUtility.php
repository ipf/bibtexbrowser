<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser\Utility;

class InternationalizationUtility
{
    public static function translate($msg)
    {
        global $BIBTEXBROWSER_LANG;
        if (isset($BIBTEXBROWSER_LANG[$msg])) {
            return $BIBTEXBROWSER_LANG[$msg];
        }

        return $msg;
    }
}
