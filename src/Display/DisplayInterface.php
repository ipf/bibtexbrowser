<?php

namespace BibtexBrowser\BibtexBrowser\Display;

/**
 * displays the summary information of all bib entries.
 */
interface DisplayInterface
{
    /** sets the entries to be shown */
    public function setEntries($entries);

    /** Displays a set of bibtex entries in an HTML table */
    public function display(): void;
}
