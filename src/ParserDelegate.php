<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** a default empty implementation of a delegate for StateBasedBibtexParser */
class ParserDelegate
{
    public function beginFile()
    {
    }

    public function endFile()
    {
    }

    public function setEntryField($finalkey, $entryvalue)
    {
    }

    public function setEntryType($entrytype)
    {
    }

    public function setEntryKey($entrykey)
    {
    }

    public function beginEntry()
    {
    }

    public function endEntry($entrysource)
    {
    }

    /** called for each sub parts of type {part} of a field value
     * for now, only CURLYTOP and CURLYONE events
    */
    public function entryValuePart($key, $value, $type)
    {
    }
} // end class ParserDelegate
