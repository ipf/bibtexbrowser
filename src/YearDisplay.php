<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** displays the entries by year in reverse chronological order.
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $d = new YearDisplay();
 * $d->setDB($db);
 * $d->display();
 * </pre>
 */
class YearDisplay
{
    public $entries;

    /** is an array of strings representing years */
    public ?array $yearIndex = null;

    public function setDB($bibdatabase)
    {
        $this->setEntries($bibdatabase->bibdb);
    }

    /** creates a YearDisplay */
    public function setOptions($options)
    {
    }

    public function getTitle()
    {
        return '';
    }

    /** sets the entries to be shown */
    public function setEntries($entries)
    {
        $this->entries = $entries;
        $db = new BibDataBase();
        $db->bibdb = $entries;
        $this->yearIndex = $db->yearIndex();
    }

    /** Displays a set of bibtex entries in an HTML table */
    public function display()
    {
        $delegate = new SimpleDisplay();
        $delegate->setEntries($this->entries);

        $index = count($this->entries);
        foreach ($this->yearIndex as $year) {
            $x = [];
            uasort($x, 'compare_bib_entry_by_month');
            foreach ($this->entries as $e) {
                if ($e->getYear() == $year) {
                    $x[] = $e;
                }
            }

            if ($x !== []) {
                echo '<div  class="theader">' . $year . '</div>';
                $delegate->setEntries($x);
                $delegate->display();
            }

            $index -= count($x);
        }
    }
}
