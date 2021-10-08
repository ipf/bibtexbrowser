<?php

namespace BibtexBrowser\BibtexBrowser;

/** displays a single bib entry.
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $dis = new BibEntryDisplay($db->getEntryByKey('classical'));
 * $dis->display();
 * </pre>
 * notes:
 * - the top-level header (usually &lt;H1>) must be done by the caller.
 * - this view is optimized for Google Scholar
 */
class BibEntryDisplay
{
    public function __construct(
        /** the bib entry to display */
        public $bib = null
    ) {
    }

    public function setEntries($entries)
    {
        $this->bib = $entries[0];
        //$this->title = $this->bib->getTitle().' (bibtex)'.$this->bib->getUrlLink();
    }

    /** returns the title */
    public function getTitle()
    {
        return $this->bib->getTitle() . ' (bibtex)';
    }

    /** 2011/10/02: new display, inspired from Tom Zimmermann's home page */
    public function displayOnSteroids()
    {
        $subtitle = '<div class="bibentry-by">by ' . $this->bib->getFormattedAuthorsString() . '</div>';

        $abstract = '';
        if ($this->bib->hasField('abstract')) {
            $abstract = '<div class="bibentry-label">Abstract:</div><div class="bibentry-abstract">' . $this->bib->getAbstract() . '</div>';
        }

        $download = '';
        if ($this->bib->hasField('url')) {
            $download = '<div class="bibentry-document-link"><a href="' . $this->bib->getField('url') . '">View PDF</a></div>';
        }

        $reference = '<div class="bibentry-label">Reference:</div><div class="bibentry-reference">' . strip_tags(bib2html($this->bib)) . '</div>';

        $bibtex = '<div class="bibentry-label">Bibtex Entry:</div>' . $this->bib->toEntryUnformatted() . '';
        return $subtitle . $abstract . $download . $reference . $bibtex . $this->bib->toCoins();
    }

    public function display()
    {
        // we encapsulate everything so that the output of display() is still valid XHTML
        echo '<div>';
        //echo $this->display_old();
        echo $this->displayOnSteroids();
        echo '</div>';
    }

    // old display
    public function display_old()
    {
        return $this->bib->toCoins() . $this->bib->toEntryUnformatted();
    }

    /** Returns a dictionary of metadata. If the same metadata appears multiple times, it is concatenated with ";"
     */
    public function metadata_dict()
    {
        $result = [];
        foreach ($this->metadata() as $v) {
            if (!in_array($v[0], $result)) {
                $result[$v[0]] = $v[1];
            } else {
                $result[$v[0]] .= ';' . $v[1];
            }
        }

        return $result;
    }

    /** Returns an array containing the metadata for Google Scholar
     *    array (array('citation_title', 'foo'), ....)
     * @see http://scholar.google.com/intl/en/scholar/inclusion.html
     * @see http://www.monperrus.net/martin/accurate+bibliographic+metadata+and+google+scholar
     * */
    public function metadata()
    {
        $result = [];

        if (c('BIBTEXBROWSER_ROBOTS_NOINDEX')) {
            $result[] = ['robots', 'noindex'];
        }

        if (c('METADATA_GS')) {
            $result = $this->metadata_google_scholar($result);
        }

        // end Google Scholar

        // a fallback to essential dublin core
        if (c('METADATA_DC')) {
            $result = $this->metadata_dublin_core($result);
        }

        if (c('METADATA_OPENGRAPH')) {
            $result = $this->metadata_opengraph($result);
        }

        if (c('METADATA_EPRINTS')) {
            $result = $this->metadata_eprints($result);
        }

        return $result;
    }

    // end function metadata

    public function metadata_opengraph($result)
    {
        // Facebook metadata
        // see http://ogp.me
        // https://developers.facebook.com/tools/debug/og/object/
        $result[] = ['og:type', 'article'];
        $result[] = ['og:title', $this->bib->getTitle()];
        foreach ($this->bib->getRawAuthors() as $author) {
            // opengraph requires a URL as author value
            $result[] = [
                'og:author',
                'http://' . @$_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '?bib=' . urlencode($this->bib->filename) . '&amp;author=' . urlencode($author)
            ];
        }

        $result[] = ['og:published_time', $this->bib->getYear()];
        return $result;
    }

    // end function metadata_opengraph

    public function metadata_dublin_core($result)
    {
        // Dublin Core should not be used for bibliographic metadata
        // according to several sources
        //  * Google Scholar: "Use Dublin Core tags (e.g., DC.title) as a last resort - they work poorly for journal papers"
        //  * http://reprog.wordpress.com/2010/09/03/bibliographic-data-part-2-dublin-cores-dirty-little-secret/
        // however it seems that Google Scholar needs at least DC.Title to trigger referencing
        // reference documentation: http://dublincore.org/documents/dc-citation-guidelines/
        $result[] = ['DC.Title', $this->bib->getTitle()];
        foreach ($this->bib->getArrayOfCommaSeparatedAuthors() as $author) {
            $result[] = ['DC.Creator', $author];
        }

        $result[] = ['DC.Issued', $this->bib->getYear()];
        return $result;
    }

    public function metadata_google_scholar($result)
    {
        // the description may mix with the Google Scholar tags
        // we remove it
        // $result[] = array('description',trim(strip_tags(str_replace('"','',bib2html($this->bib)))));
        $result[] = ['citation_title', $this->bib->getTitle()];
        $authors = $this->bib->getArrayOfCommaSeparatedAuthors();
        $result[] = ['citation_authors', implode('; ', $authors)];
        foreach ($authors as $author) {
            $result[] = ['citation_author', $author];
        }

        // the date
        $result[] = ['citation_publication_date', $this->bib->getYear()];
        $result[] = ['citation_date', $this->bib->getYear()];
        $result[] = ['citation_year', $this->bib->getYear()];

        if ($this->bib->hasField('publisher')) {
            $result[] = ['citation_publisher', $this->bib->getPublisher()];
        }

        // BOOKTITLE: JOURNAL NAME OR PROCEEDINGS
        if ($this->bib->getType() == 'article') { // journal article
            $result[] = ['citation_journal_title', $this->bib->getField('journal')];
            $result[] = ['citation_volume', $this->bib->getField('volume')];
            if ($this->bib->hasField('number')) {
                // in bibtex, the issue number is usually in a field "number"
                $result[] = ['citation_issue', $this->bib->getField('number')];
            }

            if ($this->bib->hasField('issue')) {
                $result[] = ['citation_issue', $this->bib->getField('issue')];
            }

            if ($this->bib->hasField('issn')) {
                $result[] = ['citation_issue', $this->bib->getField('issn')];
            }
        }

        if ($this->bib->getType() == 'inproceedings' || $this->bib->getType() == 'conference') {
            $result[] = ['citation_conference_title', $this->bib->getField(BOOKTITLE)];
            $result[] = ['citation_conference', $this->bib->getField(BOOKTITLE)];
        }

        if ($this->bib->getType() == 'phdthesis'
            || $this->bib->getType() == 'mastersthesis'
            || $this->bib->getType() == 'bachelorsthesis'
        ) {
            $result[] = ['citation_dissertation_institution', $this->bib->getField('school')];
        }

        if ($this->bib->getType() == 'techreport'
            && $this->bib->hasField('number')
        ) {
            $result[] = ['citation_technical_report_number', $this->bib->getField('number')];
        }

        if ($this->bib->getType() == 'techreport'
            && $this->bib->hasField('institution')
        ) {
            $result[] = ['citation_technical_report_institution', $this->bib->getField('institution')];
        }

        // generic
        if ($this->bib->hasField('doi')) {
            $result[] = ['citation_doi', $this->bib->getField('doi')];
        }

        if ($this->bib->hasField('url')) {
            $result[] = ['citation_pdf_url', $this->bib->getField('url')];
        }

        if ($this->bib->hasField('pages')) {
            $pages = $this->bib->getPages();
            if (count($pages) == 2) {
                $result[] = ['citation_firstpage', $pages[0]];
                $result[] = ['citation_lastpage', $pages[1]];
            }
        }

        return $result;
    }

    public function metadata_eprints($result)
    {
        // --------------------------------- BEGIN METADATA EPRINTS
        // and now adding eprints metadata
        // why adding eprints metadata?
        // because eprints is a well known bibliographic software and several crawlers/desktop software
        // use their metadata
        // unfortunately, the metadata is even less documented than Google Scholar citation_
        // reference documentation: the eprints source code (./perl_lib/EPrints/Plugin/Export/Simple.pm)
        // examples: conference paper: http://tubiblio.ulb.tu-darmstadt.de/44344/
        //           journal paper: http://tubiblio.ulb.tu-darmstadt.de/44344/
        $result[] = ['eprints.title', $this->bib->getTitle()];
        $authors = $this->bib->getArrayOfCommaSeparatedAuthors();
        foreach ($authors as $author) {
            $result[] = ['eprints.creators_name', $author];
        }

        $result[] = ['eprints.date', $this->bib->getYear()];

        if ($this->bib->hasField('publisher')) {
            $result[] = ['eprints.publisher', $this->bib->getPublisher()];
        }

        if ($this->bib->getType() == 'article') { // journal article
            $result[] = ['eprints.type', 'article'];
            $result[] = ['eprints.publication', $this->bib->getField('journal')];
            $result[] = ['eprints.volume', $this->bib->getField('volume')];
            if ($this->bib->hasField('issue')) {
                $result[] = ['eprints.number', $this->bib->getField('issue')];
            }
        }

        if ($this->bib->getType() == 'inproceedings' || $this->bib->getType() == 'conference') {
            $result[] = ['eprints.type', 'proceeding'];
            $result[] = ['eprints.book_title', $this->bib->getField(BOOKTITLE)];
        }

        if ($this->bib->getType() == 'phdthesis'
            || $this->bib->getType() == 'mastersthesis'
            || $this->bib->getType() == 'bachelorsthesis'
        ) {
            $result[] = ['eprints.type', 'thesis'];
            $result[] = ['eprints.institution', $this->bib->getField('school')];
        }

        if ($this->bib->getType() == 'techreport') {
            $result[] = ['eprints.type', 'monograph'];
            if ($this->bib->hasField('number')) {
                $result[] = ['eprints.number', $this->bib->getField('number')];
            }

            if ($this->bib->hasField('institution')) {
                $result[] = ['eprints.institution', $this->bib->getField('institution')];
            }
        }

        // generic
        if ($this->bib->hasField('doi')) {
            $result[] = ['eprints.id_number', $this->bib->getField('doi')];
        }

        if ($this->bib->hasField('url')) {
            $result[] = ['eprints.official_url', $this->bib->getField('url')];
        }

        // --------------------------------- END METADATA EPRINTS
        return $result;
    }

    // end method metatada_eprints;
}

 // end class BibEntryDisplay
