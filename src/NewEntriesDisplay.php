<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** displays the latest modified bibtex entries.
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $d = new NewEntriesDisplay();
 * $d->setDB($db);
 * $d->setN(7);// optional
 * $d->display();
 * </pre>
 */
class NewEntriesDisplay
{
    public int $n = 5;

    public $db;

    public function setDB($bibdatabase)
    {
        $this->db = $bibdatabase;
    }

    public function setN($n)
    {
        $this->n = $n;
        return $this;
    }

    /** sets the entries to be shown */
    public function setEntries($entries)
    {
        $this->db = new BibDataBase();
        $this->db->bibdb = $entries;
    }

    /** Displays a set of bibtex entries in an HTML table */
    public function display()
    {
        $array = $this->db->getLatestEntries($this->n);
        $delegate = new SimpleDisplay();
        $delegate->setEntries($array);
        $delegate->display();
    }
}
