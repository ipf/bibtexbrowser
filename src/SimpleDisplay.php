<?php

namespace BibtexBrowser\BibtexBrowser;

use BibtexBrowser\BibtexBrowser\Utility\InternationalizationUtility;
use BibtexBrowser\BibtexBrowser\Utility\TemplateUtility;

/** displays the summary information of all bib entries.
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $d = new SimpleDisplay();
 * $d->setDB($db);
 * $d->display();
 * </pre>
 */
class SimpleDisplay
{
    public $query;

    public string $headerCSS = 'sheader';

    public array $options = [];

    public array $entries = [];

    public $headingLevel = BIBTEXBROWSER_HTMLHEADINGLEVEL;

    public function __construct($db = null, $query = [])
    {
        if ($db == null) {
            return;
        }

        $this->setEntries($db->multisearch($query));
    }

    public function incHeadingLevel($by = 1)
    {
        $this->headingLevel += $by;
    }

    public function decHeadingLevel($by = 1)
    {
        $this->headingLevel -= $by;
    }

    public function setDB($bibdatabase)
    {
        $this->setEntries($bibdatabase->bibdb);
    }

    public function metadata()
    {
        if (BIBTEXBROWSER_ROBOTS_NOINDEX) {
            return [['robots', 'noindex']];
        } else {
            return [];
        }
    }

    /** sets the entries to be shown */
    public function setEntries($entries)
    {
        $this->entries = array_values($entries);
    }

    public function indexUp()
    {
        $index = 1;
        foreach ($this->entries as $bib) {
            $bib->setAbbrv((string)$index++);
        }

        return $this->entries;
    }

    public function newest($entries)
    {
        return array_slice($entries, 0, BIBTEXBROWSER_NEWEST);
    }

    public function indexDown()
    {
        $index = count($this->entries);
        foreach ($this->entries as $bib) {
            $bib->setAbbrv((string)$index--);
        }

        return $this->entries;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function getTitle()
    {
        return _DefaultBibliographyTitle($this->query);
    }

    public function setIndices()
    {
        $this->setIndicesInDecreasingOrder();
    }

    public function setIndicesInIncreasingOrderChangingEveryYear()
    {
        $i = 1;
        $pred = null;
        foreach ($this->entries as $bib) {
            if ($this->changeSection($pred, $bib)) {
                $i = 1;
            }

            $bib->setIndex($i++);
            $pred = $bib;
        }
    }

    public function setIndicesInDecreasingOrder()
    {
        $count = count($this->entries);
        $i = 0;
        foreach ($this->entries as $bib) {
            // by default, index are in decreasing order
            // so that when you add a publicaton recent , the indices of preceding publications don't change
            $bib->setIndex($count - ($i++));
        }
    }

    /** Displays a set of bibtex entries in an HTML table */
    public function display(): void
    {
        usort($this->entries, 'compare_bib_entries');

        // now that the entries are sorted, setting the index of entries
        // this function can be overloaded
        $this->setIndices();

        if ($this->options) {
            foreach ($this->options as $fname => $opt) {
                $this->$fname($opt, $entries);
            }
        }

        if (BIBTEXBROWSER_DEBUG) {
            echo 'Style: ' . bibtexbrowser_configuration('BIBLIOGRAPHYSTYLE') . '<br/>';
            echo 'Order: ' . ORDER_FUNCTION . '<br/>';
            echo 'Abbrv: ' . c('ABBRV_TYPE') . '<br/>';
            echo 'Options: ' . @implode(',', $this->options) . '<br/>';
        }

        TemplateUtility::print_header_layout();

        $pred = null;
        foreach ($this->entries as $bib) {
            if ($this->changeSection($pred, $bib)) {
                echo $this->sectionHeader($bib, $pred);
            }

            echo $bib->toHTML(true);

            $pred = $bib;
        }

        TemplateUtility::print_footer_layout();
    }

    public function changeSection($pred, $bib): bool
    {
        // for the first one we output the header
        if ($pred == null) {
            return true;
        }

        $f = ORDER_FUNCTION;
        return $f($pred, $bib) != 0;
    }

    public function sectionHeader($bib, $pred): string
    {
        switch (BIBTEXBROWSER_LAYOUT) {
            case 'table':
                return '<tr><td colspan="2" class="' . $this->headerCSS . '">' . $bib->getYear() . '</td></tr>' . "\n";
                break;
            case 'definition':
                return '<div class="' . $this->headerCSS . '">' . $bib->getYear() . '</div>' . "\n";
                break;
            case 'list':
                $string = '';
                if ($pred) {
                    $string .= "</ul>\n";
                }

                $year = $bib->hasField(YEAR) ? $bib->getYear() : InternationalizationUtility::translate('No date');
                return $string . '<h' . $this->headingLevel . '>' . $year . '</h' . $this->headingLevel . ">\n<ul class=\"result\">\n";
            default:
                return '';
        }
    }
}
