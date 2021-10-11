<?php

namespace BibtexBrowser\BibtexBrowser\Display;

use BibtexBrowser\BibtexBrowser\BibDataBase;
use BibtexBrowser\BibtexBrowser\Configuration\Configuration;
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
class SimpleDisplay implements DisplayInterface
{
    private $query;

    public string $headerCSS = 'sheader';

    private array $options = [];

    public array $entries = [];

    private int $headingLevel = Configuration::BIBTEXBROWSER_HTMLHEADINGLEVEL;

    public function __construct(?BibDataBase $db = null, array $query = [])
    {
        if ($db === null) {
            return;
        }

        $this->setEntries($db->multisearch($query));
    }

    public function incHeadingLevel($by = 1): void
    {
        $this->headingLevel += $by;
    }

    public function decHeadingLevel($by = 1): void
    {
        $this->headingLevel -= $by;
    }

    public function setDB($bibdatabase): void
    {
        $this->setEntries($bibdatabase->bibdb);
    }

    public function metadata(): array
    {
        if (Configuration::BIBTEXBROWSER_ROBOTS_NOINDEX) {
            return [['robots', 'noindex']];
        }

        return [];
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
        return array_slice($entries, 0, Configuration::BIBTEXBROWSER_NEWEST);
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
                $this->$fname($opt, $this->entries);
            }
        }

        if (Configuration::BIBTEXBROWSER_DEBUG) {
            echo 'Style: ' . Configuration::bibtexbrowser_configuration('BIBLIOGRAPHYSTYLE') . '<br/>';
            echo 'Order: ' . Configuration::ORDER_FUNCTION . '<br/>';
            echo 'Abbrv: ' . Configuration::c('ABBRV_TYPE') . '<br/>';
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

        $f = Configuration::ORDER_FUNCTION;
        return $f($pred, $bib) != 0;
    }

    public function sectionHeader($bib, $pred): string
    {
        switch (Configuration::BIBTEXBROWSER_LAYOUT) {
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

                $year = $bib->hasField(Configuration::YEAR) ? $bib->getYear() : InternationalizationUtility::translate('No date');
                return $string . '<h' . $this->headingLevel . '>' . $year . '</h' . $this->headingLevel . ">\n<ul class=\"result\">\n";
            default:
                return '';
        }
    }
}
