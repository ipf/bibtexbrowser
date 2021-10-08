<?php

namespace BibtexBrowser\BibtexBrowser;

/** is used to create an subset of a bibtex file.
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $query = array('year'=>2005);
 * $dis = new BibtexDisplay();
 * $dis->setEntries($db->multisearch($query));
 * $dis->display();
 * </pre>
 */
class BibtexDisplay
{
    public $title;

    public $entries;

    public function __construct()
    {
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /** sets the entries to be shown */
    public function setEntries($entries)
    {
        $this->entries = $entries;
    }

    public function setWrapper($x)
    {
        $x->wrapper = 'NoWrapper';
    }

    public function display()
    {
        header('Content-type: text/plain; charset=' . OUTPUT_ENCODING);
        echo '% generated by bibtexbrowser <http://www.monperrus.net/martin/bibtexbrowser/>' . "\n";
        echo '% ' . @$this->title . "\n";
        echo '% Encoding: ' . OUTPUT_ENCODING . "\n";
        foreach ($this->entries as $bibentry) {
            echo $bibentry->getText() . "\n";
        }
    }
}
