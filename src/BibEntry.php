<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

use BibtexBrowser\BibtexBrowser\Configuration\Configuration;
use BibtexBrowser\BibtexBrowser\Utility\CharacterUtility;
use BibtexBrowser\BibtexBrowser\Utility\InternationalizationUtility;

/** represents a bibliographic entry.
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $entry = $db->getEntryByKey('classical');
 * echo bib2html($entry);
 * </pre>
 * notes:
 * - BibEntry are usually obtained with getEntryByKey or multisearch
 */
class BibEntry implements \Stringable
{
    // used for representing the type of the bibtex entry internally
    /**
     * @var string
     */
    public const Q_INNER_TYPE = 'x-bibtex-type';

    /** The fields (fieldName -> value) of this bib entry with Latex macros interpreted and encoded in the desired character set . */
    public array $fields = [];

    /** The raw fields (fieldName -> value) of this bib entry. */
    public array $raw_fields = [];

    /** The constants @STRINGS referred to by this entry */
    public array $constants = [];

    /** The homepages of authors if any */
    public array $homepages = [];

    /** The crossref entry if there is one */
    public $crossref;

    /** The verbatim copy (i.e., whole text) of this bib entry. */
    public string $text = '';

    /** A timestamp to trace when entries have been created */
    public ?int $timestamp = null;

    /** The name of the file containing this entry */
    public $filename;

    /** The short name of the entry (parameterized by ABBRV_TYPE) */
    public $abbrv;

    /** The index in a list of publications (e.g. [1] Foo */
    public ?string $index = '';

    /** The location in the original bibtex file (set by addEntry) */
    public int $order = -1;


    /** returns a debug string representation */
    public function __toString(): string
    {
        return $this->getType() . ' ' . $this->getKey();
    }

    /** Creates an empty new bib entry. Each bib entry is assigned a unique
     * identification number. */
    public function __construct()
    {
    }

    /** Sets the name of the file containing this entry */
    public function setFile($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /** Adds timestamp to this object */
    public function timestamp(): void
    {
        $this->timestamp = time();
    }

    /** Returns the timestamp of this object */
    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    /** Returns the type of this bib entry. */
    public function getType(): string
    {
        // strtolower is important to be case-insensitive
        return strtolower($this->getField(self::Q_INNER_TYPE));
    }

    /** Sets the key of this bib entry. */
    public function setKey($value): void
    {
        // Slashes are not allowed in keys because they don't play well with web servers
        // if url-rewriting is used
        $this->setField(Configuration::Q_KEY, str_replace('/', '-', $value));
    }

    public function transformValue(string $value): string
    {
        if (Configuration::BIBTEXBROWSER_USE_LATEX2HTML) {
            // trim space
            $value = CharacterUtility::xtrim($value);

            // transform Latex markup to HTML entities (easier than a one to one mapping to each character)
            // HTML entity is an intermediate format
            $value = CharacterUtility::latex2html($value);

            // transform to the target output encoding
            $value = html_entity_decode($value, ENT_QUOTES | ENT_XHTML, Configuration::OUTPUT_ENCODING);
        }

        return $value;
    }

    /** removes a field from this bibtex entry */
    public function removeField($name): void
    {
        $name = strtolower($name);
        unset($this->raw_fields[$name], $this->fields[$name]);
    }

    /** Sets a field of this bib entry. */
    public function setField($name, $value)
    {
        $name = strtolower($name);
        $this->raw_fields[$name] = $value;

        // fields that should not be transformed
        // we assume that "comment" is never latex code
        // but instead could contain HTML code (with links using the character "~" for example)
        // so "comment" is not transformed too
        if ($name !== 'url' && $name !== 'comment'
            && !str_starts_with($name, 'hp_') // homepage links should not be transformed with latex2html
        ) {
            $value = $this->transformValue($value);

            // 4. transform existing encoded character in the new format
            if (function_exists('mb_convert_encoding') && Configuration::OUTPUT_ENCODING !== Configuration::BIBTEX_INPUT_ENCODING) {
                $value = mb_convert_encoding($value, Configuration::OUTPUT_ENCODING, Configuration::BIBTEX_INPUT_ENCODING);
            }
        }

        $this->fields[$name] = $value;
    }

    public function clean_top_curly($value)
    {
        $value = preg_replace('#^\{#', '', $value);
        return preg_replace('#\}$#', '', $value);
    }

    /** Sets a type of this bib entry. */
    public function setType($value): void
    {
        // 2009-10-25 added trim
        // to support space e.g. "@article  {"
        // as generated by ams.org
        // thanks to Jacob Kellner
        $this->fields[self::Q_INNER_TYPE] = trim($value);
    }

    public function setIndex($index): void
    {
        $this->index = (string)$index;
    }

    /** Tries to build a good URL for this entry. The URL should be absolute (better for the generated RSS) */
    public function getURL(): string
    {
        if (defined('BIBTEXBROWSER_URL_BUILDER')) {
            $f = BIBTEXBROWSER_URL_BUILDER;
            return $f($this);
        }

        return Configuration::BIBTEXBROWSER_URL . '?' . createQueryString([Configuration::Q_KEY => $this->getKey(), Configuration::Q_FILE => $this->filename]);
    }

    /** @see bib2links(), kept for backward compatibility */
    public function bib2links()
    {
        return bib2links($this);
    }

    /**
     * Read the bibtex field $bibfield and return a link with icon (if $iconurl is given) or text
     * e.g. given the bibtex entry: @article{myarticle, pdf={myarticle.pdf}},
     * $bibtexentry->getLink('pdf') creates a link to myarticle.pdf using the text '[pdf]'.
     * $bibtexentry->getLink('pdf','pdficon.png') returns &lt;a href="myarticle.pdf">&lt;img src="pdficon.png"/>&lt;/a>
     * if you want a label that is different from the bibtex field, add a third parameter.
     */
    public function getLink(string $bibfield, ?string $iconurl = null, ?string $altlabel = null): string
    {
        $show = true;
        if ($altlabel == null) {
            $altlabel = $bibfield;
        }

        $str = $this->getIconOrTxt($altlabel, $iconurl);
        if ($this->hasField($bibfield)) {
            return '<a' . get_target() . ' href="' . $this->getField($bibfield) . '">' . $str . '</a>';
        }

        return '';
    }

    /** returns a "[bib]" link */
    public function getBibLink(?string $iconurl = null): string
    {
        $bibstr = $this->getIconOrTxt('bibtex', $iconurl);
        $href = 'href="' . $this->getURL() . '"';
        // we add biburl and title to be able to retrieve this important information
        // using Xpath expressions on the XHTML source
        return '<a' . get_target() . ' class="biburl" title="' . $this->getKey() . sprintf('" %s>%s</a>', $href, $bibstr);
    }

    /** kept for backward compatibility */
    public function getPdfLink(?string $iconurl = null, ?string $label = null): string
    {
        return $this->getUrlLink($iconurl);
    }

    /** returns a "[pdf]" link for the entry, if possible.
     * Tries to get the target URL from the 'pdf' field first, then from 'url' or 'file'.
     * Performs a sanity check that the file extension is 'pdf' or 'ps' and uses that as link label.
     * Otherwise (and if no explicit $label is set) the field name is used instead.
     */
    public function getUrlLink(?string $iconurl = null): string
    {
        if ($this->hasField('pdf')) {
            return $this->getAndRenameLink('pdf', $iconurl);
        }

        if ($this->hasField('url')) {
            return $this->getAndRenameLink('url', $iconurl);
        }

        // Adding link to PDF file exported by Zotero
        // ref: https://github.com/monperrus/bibtexbrowser/pull/14
        if ($this->hasField('file')) {
            return $this->getAndRenameLink('file', $iconurl);
        }

        return '';
    }

    /** See description of 'getUrlLink'
     */
    public function getAndRenameLink(string $bibfield, ?string $iconurl = null): string
    {
        $extension = strtolower(pathinfo(parse_url($this->getField($bibfield), PHP_URL_PATH), PATHINFO_EXTENSION));
        return match ($extension) {
            'html' => $this->getLink($bibfield, $iconurl, 'html'),
            'pdf' => $this->getLink($bibfield, $iconurl, 'pdf'),
            'ps' => $this->getLink($bibfield, $iconurl, 'ps'),
            default => $this->getLink($bibfield, $iconurl, $bibfield),
        };
    }


    /** DOI are a special kind of links, where the url depends on the doi */
    public function getDoiLink(?string $iconurl = null): string
    {
        $str = $this->getIconOrTxt('doi', $iconurl);
        if ($this->hasField('doi')) {
            return '<a' . get_target() . ' href="https://doi.org/' . $this->getField('doi') . '">' . $str . '</a>';
        }

        return '';
    }

    /** GS (Google Scholar) are a special kind of links, where the url depends on the google scholar id */
    public function getGSLink($iconurl = null)
    {
        $str = $this->getIconOrTxt('citations', $iconurl);
        if ($this->hasField('gsid')) {
            return ' <a' . get_target() . ' href="https://scholar.google.com/scholar?cites=' . $this->getField('gsid') . '">' . $str . '</a>';
        }

        return '';
    }

    /** replace [$ext] with an icon whose url is defined in a string
     *  e.g. getIconOrTxt('pdf') will print '[pdf]'
     *  or   getIconOrTxt('pdf','http://link/to/icon.png') will use the icon linked by the url, or print '[pdf']
     *  if the url does not point to a valid file (using the "alt" property of the "img" html tag)
     */
    public function getIconOrTxt(string $txt, ?string $iconurl = null)
    {
        if ($iconurl === null) {
            $str = '[' . $txt . ']';
        } else {
            $str = '<img class="icon" src="' . $iconurl . '" alt="[' . $txt . ']" title="' . $txt . '"/>';
        }

        return $str;
    }

    /** Reruns the abstract */
    public function getAbstract()
    {
        if ($this->hasField('abstract')) {
            return $this->getField('abstract');
        }

        return '';
    }

    /**
     * Returns the last name of an author name.
     */
    public function getLastName($author)
    {
        [$firstname, $lastname] = splitFullName($author);
        return $lastname;
    }

    /**
     * Returns the first name of an author name.
     */
    public function getFirstName($author)
    {
        list($firstname, $lastname) = splitFullName($author);
        return $firstname;
    }

    /** Has this entry the given field? */
    public function hasField($name)
    {
        return isset($this->fields[strtolower($name)]);
    }

    /** Returns the authors of this entry. If "author" is not given,
     * return a string 'Unknown'. */
    public function getAuthor()
    {
        if (array_key_exists(Configuration::AUTHOR, $this->fields)) {
            return $this->getFormattedAuthorsString();
        }

        // 2010-03-02: commented the following, it results in misleading author lists
        // issue found by Alan P. Sexton
        return 'Unknown';
    }

    /** Returns the key of this entry */
    public function getKey()
    {
        return $this->getField(Configuration::Q_KEY);
    }

    /** Returns the title of this entry? */
    public function getTitle()
    {
        return $this->getField('title');
    }

    /** Returns the publisher of this entry
     * It encodes a specific logic
     * */
    public function getPublisher()
    {
        // citation_publisher
        if ($this->hasField('publisher')) {
            return $this->getField('publisher');
        }

        if ($this->getType() === 'phdthesis') {
            return $this->getField(Configuration::SCHOOL);
        }

        if ($this->getType() === 'mastersthesis') {
            return $this->getField(Configuration::SCHOOL);
        }

        if ($this->getType() === 'bachelorsthesis') {
            return $this->getField(Configuration::SCHOOL);
        }

        if ($this->getType() === 'techreport') {
            return $this->getField('institution');
        }

        // then we don't know
        return '';
    }

    /** Returns the authors of this entry as an array (split by " and ") */
    public function getRawAuthors()
    {
        return $this->split_authors();
    }

    public function split_authors()
    {
        $array = [];
        if (array_key_exists(Configuration::Q_AUTHOR, $this->raw_fields)) {
            $array = preg_split('# and( |$)#ims', @$this->raw_fields[Configuration::Q_AUTHOR]);
        }

        $res = [];
        $arrayCount = count($array);
        // we merge the remaining ones
        for ($i = 0; $i < $arrayCount - 1; ++$i) {
            if (str_contains(CharacterUtility::latex2html($array[$i], false), '{') && str_contains(
                CharacterUtility::latex2html($array[$i + 1], false),
                '}'
            )) {
                $res[] = $this->clean_top_curly(trim($array[$i]) . ' and ' . trim($array[$i + 1]));
                ++$i;
            } else {
                $res[] = trim($array[$i]);
            }
        }

        if (count($array) > 0 && !preg_match('#\}#', CharacterUtility::latex2html($array[count($array) - 1], false))) {
            $res[] = trim($array[count($array) - 1] ?? '');
        }

        return $res;
    }

    /**
     * Returns the formated author name w.r.t to the user preference
     * encoded in USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT and USE_INITIALS_FOR_NAMES
     */
    public function formatAuthor($author)
    {
        $author = $this->transformValue($author);
        if (Configuration::USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT) {
            return $this->formatAuthorCommaSeparated($author);
        }

        if (Configuration::USE_INITIALS_FOR_NAMES) {
            return $this->formatAuthorInitials($author);
        }

        if (Configuration::USE_FIRST_THEN_LAST) {
            return $this->formatAuthorCanonical($author);
        }

        return $author;
    }

    /**
     * Returns the formated author name as "FirstName LastName".
     */
    public function formatAuthorCanonical($author)
    {
        list($firstname, $lastname) = splitFullName($author);
        if ($firstname != '') {
            return $firstname . ' ' . $lastname;
        }

        return $lastname;
    }

    /**
     * Returns the formated author name as "LastName, FirstName".
     */
    public function formatAuthorCommaSeparated($author)
    {
        list($firstname, $lastname) = splitFullName($author);
        if ($firstname != '') {
            return $lastname . ', ' . $firstname;
        }

        return $lastname;
    }

    /**
     * Returns the formated author name as "LastName Initials".
     * e.g. for Vancouver-style used by PubMed.
     */
    public function formatAuthorInitials($author)
    {
        list($firstname, $lastname) = splitFullName($author);
        if ($firstname != '') {
            return $lastname . ' ' . preg_replace("#(\p{Lu})\w*[- ]*#Su", '$1', $firstname);
        }

        return $lastname;
    }


    /** @deprecated */
    public function formattedAuthors()
    {
        return $this->getFormattedAuthorsString();
    }

    /** @deprecated */
    public function getFormattedAuthors()
    {
        return $this->getFormattedAuthorsArray();
    }

    /** @deprecated */
    public function getFormattedAuthorsImproved()
    {
        return $this->getFormattedAuthorsString();
    }


    /** Returns the authors as an array of strings (one string per author).
     */
    public function getFormattedAuthorsArray()
    {
        $array_authors = [];


        // first we use formatAuthor
        foreach ($this->getRawAuthors() as $author) {
            $array_authors[] = $this->formatAuthor($author);
        }

        if (Configuration::BIBTEXBROWSER_AUTHOR_LINKS === 'homepage') {
            foreach ($array_authors as $k => $author) {
                $array_authors[$k] = $this->addHomepageLink($author);
            }
        }

        if (Configuration::BIBTEXBROWSER_AUTHOR_LINKS === 'resultpage') {
            foreach ($array_authors as $k => $author) {
                $array_authors[$k] = $this->addAuthorPageLink($author);
            }
        }

        return $array_authors;
    }

    /** Adds to getFormattedAuthors() the home page links and returns a string (not an array). Is configured with BIBTEXBROWSER_AUTHOR_LINKS and USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT.
     */
    public function getFormattedAuthorsString(): string
    {
        return $this->implodeAuthors($this->getFormattedAuthorsArray());
    }

    public function implodeAuthors($authors): string
    {
        if (count($authors) === 0) {
            return '';
        }

        if (count($authors) === 1) {
            return $authors[0];
        }

        $result = '';

        $sep = Configuration::USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT ? '; ' : ', ';
        if (Configuration::FORCE_NAMELIST_SEPARATOR !== '') {
            $sep = Configuration::FORCE_NAMELIST_SEPARATOR;
        }

        $authorsCount = count($authors);
        for ($i = 0; $i < $authorsCount - 2; ++$i) {
            $result .= $authors[$i] . $sep;
        }

        $lastAuthorSeperator = Configuration::LAST_AUTHOR_SEPARATOR;
        // add Oxford comma if there are more than 2 authors
        if (Configuration::USE_OXFORD_COMMA && count($authors) > 2) {
            $lastAuthorSeperator = $sep . $lastAuthorSeperator;
            $lastAuthorSeperator = preg_replace('# {2,}#', ' ', $lastAuthorSeperator); // get rid of double spaces
        }

        return $result . ($authors[count($authors) - 2] . $lastAuthorSeperator . $authors[count($authors) - 1]);
    }

    /** adds a link to the author page */
    public function addAuthorPageLink($author)
    {
        $link = makeHref([Configuration::Q_AUTHOR => $author]);
        return sprintf('<a %s>%s</a>', $link, $author);
    }


    /** Returns the authors of this entry as an array in a canonical form */
    public function getCanonicalAuthors()
    {
        $authors = [];
        foreach ($this->getRawAuthors() as $author) {
            $authors[] = $this->formatAuthorCanonical($author);
        }

        return $authors;
    }

    /** Returns the authors of this entry as an array in a comma-separated form
     * Mostly used to create meta tags (eg <meta>
     */
    public function getArrayOfCommaSeparatedAuthors()
    {
        $authors = [];
        foreach ($this->getRawAuthors() as $author) {
            $author = $this->transformValue($author);
            $authors[] = $this->formatAuthorCommaSeparated($author);
        }

        return $authors;
    }

    /**
     * Returns a compacted string form of author names by throwing away
     * all author names except for the first one and appending ", et al."
     */
    public function getCompactedAuthors()
    {
        $authors = $this->getRawAuthors();
        $etal = count($authors) > 1 ? ', et al.' : '';
        return $this->formatAuthor($authors[0]) . $etal;
    }

    public function getHomePageKey($author)
    {
        return strtolower('hp_' . preg_replace('# #', '', $this->formatAuthorCanonical(CharacterUtility::latex2html($author))));
    }

    /** add the link to the homepage if it is defined in a string
     *  e.g. @string{hp_MartinMonperrus="http://www.monperrus.net/martin"}
     *  The string is a concatenation of firstname, lastname, prefixed by hp_
     * Warning: by convention @string are case sensitive so please be keep the same case as author names
     * @thanks Eric Bodden for the idea
     */
    public function addHomepageLink($author)
    {
        // hp as home page
        // accents are normally handled
        // e.g. @STRING{hp_Jean-MarcJézéquel="http://www.irisa.fr/prive/jezequel/"}
        $homepage = $this->getHomePageKey($author);
        if (isset($this->homepages[$homepage])) {
            $author = '<a href="' . $this->homepages[$homepage] . '">' . $author . '</a>';
        }

        return $author;
    }


    /** Returns the editors of this entry as an arry */
    public function getEditors()
    {
        $editors = [];
        return preg_split('# and #i', $this->getField(Configuration::EDITOR));
    }

    /** Returns the editors of this entry as an arry
     * @throws \Exception
     */
    public function getFormattedEditors(): string
    {
        $editors = [];
        foreach ($this->getEditors() as $editor) {
            $editors[] = $this->formatAuthor($editor);
        }

        $sep = Configuration::USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT ? '; ' : ', ';
        if (Configuration::FORCE_NAMELIST_SEPARATOR !== '') {
            $sep = Configuration::FORCE_NAMELIST_SEPARATOR;
        }

        return implode($sep, $editors) . ', ' . (count($editors) > 1 ? 'eds.' : 'ed.');
    }

    /** Returns the year of this entry? */
    public function getYear(): string
    {
        return InternationalizationUtility::translate(strtolower($this->getField('year') ?? ''));
    }

    public function getYearRaw()
    {
        return $this->getField('year');
    }

    /** returns the array of keywords */
    public function getKeywords()
    {
        return preg_split('#[,;\/]#', $this->getField('keywords'));
    }

    /** Returns the value of the given field? */
    public function getField($name)
    {
        // 2010-06-07: profiling showed that this is very costly
        // hence returning the value directly
        //if ($this->hasField($name))
        //    {return $this->fields[strtolower($name)];}
        //else return 'missing '.$name;

        return @$this->fields[strtolower($name)];
    }


    /** Returns the fields */
    public function getFields()
    {
        return $this->fields;
    }

    /** Returns the raw, undecorated abbreviation depending on ABBRV_TYPE. */
    public function getRawAbbrv()
    {
        if (Configuration::ABBRV_TYPE === 'index') {
            return $this->index;
        }

        if (Configuration::ABBRV_TYPE === 'none') {
            return '';
        }

        if (Configuration::ABBRV_TYPE === 'key') {
            return $this->getKey();
        }

        if (Configuration::ABBRV_TYPE === 'year') {
            return $this->getYear();
        }

        if (Configuration::ABBRV_TYPE === 'x-abbrv') {
            if ($this->hasField('x-abbrv')) {
                return $this->getField('x-abbrv');
            }

            return $this->abbrv;
        }

        if (Configuration::ABBRV_TYPE === 'keys-index') {
            if (isset($_GET[Q_INNER_KEYS_INDEX])) {
                return $_GET[Q_INNER_KEYS_INDEX][$this->getKey()];
            }

            return '';
        }

        // otherwise it is a user-defined function in bibtexbrowser.local.php
        $f = Configuration::ABBRV_TYPE;
        return $f($this);
    }

    /** Returns the abbreviation, etc [1] if ABBRV_TYPE='index'. */
    public function getAbbrv()
    {
        $abbrv = $this->getRawAbbrv();
        if (Configuration::ABBRV_TYPE !== 'none') {
            $abbrv = '[' . $abbrv . ']';
        }

        return $abbrv;
    }

    /** Sets the abbreviation (e.g. [OOPSLA] or [1]) */
    public function setAbbrv($abbrv): static
    {
        $this->abbrv = $abbrv;
        return $this;
    }

    /** Returns the verbatim text of this bib entry.
     * @throws \Exception
     */
    public function getText(): string
    {
        if (Configuration::BIBTEXBROWSER_BIBTEX_VIEW === 'original') {
            return $this->text;
        }

        if (Configuration::BIBTEXBROWSER_BIBTEX_VIEW === 'reconstructed') {
            $result = '@' . $this->getType() . '{' . $this->getKey() . ",\n";
            foreach ($this->raw_fields as $k => $v) {
                if (!preg_match('/^(' . Configuration::BIBTEXBROWSER_BIBTEX_VIEW_FILTEREDOUT . ')$/i', $k)
                    && !preg_match('/^(key|' . Q_INNER_AUTHOR . '|' . self::Q_INNER_TYPE . ')$/i', $k)) {
                    $result .= ' ' . $k . ' = {' . $v . '},' . "\n";
                }
            }

            return $result . "}\n";
        }

        throw new \Exception('incorrect value of BIBTEXBROWSER_BIBTEX_VIEW: ' . Configuration::BIBTEXBROWSER_BIBTEX_VIEW);
    }

    /** Returns true if this bib entry contains the given phrase (PREG regexp)
     * in the given field. if $field is null, all fields are considered.
     * Note that this method is NOT case sensitive */
    public function hasPhrase(string $phrase, $field = null)
    {
        // we have to search in the formatted fields and not in the raw entry
        // i.e. all latex markups are not considered for searches
        if (!$field) {
            return preg_match('/' . $phrase . '/i', $this->getConstants() . ' ' . implode(' ', $this->getFields()));
        }

        return $this->hasField($field) && (preg_match('/' . $phrase . '/i', $this->getField($field)));
    }


    /** Outputs HTML line according to layout */
    public function toHTML($wrapped = false)
    {
        $result = '';
        if ($wrapped) {
            switch (Configuration::BIBTEXBROWSER_LAYOUT) { // open row
                case 'ordered_list':
                case 'list':
                    $result .= '<li class="bibline">';
                    break;
                case 'table':
                    $result .= '<tr class="bibline"><td class="bibref">';
                    break;
                case 'definition':
                    $result .= '<dl class="bibline"><dt class="bibref">';
                    if (Configuration::ABBRV_TYPE === 'none') {
                        die('Cannot define an empty term!');
                    }

                    break;
                case 'none':
                    break;
            }

            $result .= $this->anchor();
            if (Configuration::BIBTEXBROWSER_LAYOUT === 'table') {
                $result .= $this->getAbbrv() . '</td><td class="bibitem">';
            } elseif (Configuration::BIBTEXBROWSER_LAYOUT === 'definition') {
                $result .= $this->getAbbrv() . '</dt><dd class="bibitem">';
            }
        }

        // may be overridden using configuration value of BIBLIOGRAPHYSTYLE
        $result .= bib2html($this);

        // may be overridden using configuration value of BIBTEXBROWSER_LINK_STYLE
        $result .= ' ' . bib2links($this);

        if ($wrapped) {
            switch (Configuration::BIBTEXBROWSER_LAYOUT) { // close row
                case 'ordered_list':
                case 'list':
                    $result .= '</li>' . "\n";
                    break;
                case 'table':
                    $result .= '</td></tr>' . "\n";
                    break;
                case 'definition':
                    $result .= '</dd></dl>' . "\n";
                    break;
                case 'none':
                    break;
            }
        }

        return $result;
    }


    /** Outputs an coins URL: see http://ocoins.info/cobg.html
     * Used by Zotero, mendeley, etc.
     */
    public function toCoins()
    {
        if (Configuration::METADATA_COINS == false) {
            return;
        }

        $url_parts = [];
        $url_parts[] = 'ctx_ver=Z39.88-2004';

        $type = $this->getType();
        if ($type === 'book') {
            $url_parts[] = 'rft_val_fmt=' . CharacterUtility::s3988('info:ofi/fmt:kev:mtx:book');
            $url_parts[] = 'rft.btitle=' . CharacterUtility::s3988($this->getTitle());
            $url_parts[] = 'rft.genre=book';
        } elseif ($type === 'inproceedings') {
            $url_parts[] = 'rft_val_fmt=' . CharacterUtility::s3988('info:ofi/fmt:kev:mtx:book');
            $url_parts[] = 'rft.atitle=' . CharacterUtility::s3988($this->getTitle());
            $url_parts[] = 'rft.btitle=' . CharacterUtility::s3988($this->getField(Configuration::BOOKTITLE));

            // zotero does not support with this proceeding and conference
            // they give the wrong title
            $url_parts[] = 'rft.genre=bookitem';
        } elseif ($type === 'incollection') {
            $url_parts[] = 'rft_val_fmt=' . CharacterUtility::s3988('info:ofi/fmt:kev:mtx:book');
            $url_parts[] = 'rft.btitle=' . CharacterUtility::s3988($this->getField(Configuration::BOOKTITLE));
            $url_parts[] = 'rft.atitle=' . CharacterUtility::s3988($this->getTitle());
            $url_parts[] = 'rft.genre=bookitem';
        } elseif ($type === 'article') {
            $url_parts[] = 'rft_val_fmt=' . CharacterUtility::s3988('info:ofi/fmt:kev:mtx:journal');
            $url_parts[] = 'rft.atitle=' . CharacterUtility::s3988($this->getTitle());
            $url_parts[] = 'rft.jtitle=' . CharacterUtility::s3988($this->getField('journal'));
            $url_parts[] = 'rft.volume=' . CharacterUtility::s3988($this->getField('volume'));
            $url_parts[] = 'rft.issue=' . CharacterUtility::s3988($this->getField('issue'));
        } else { // techreport, phdthesis
            $url_parts[] = 'rft_val_fmt=' . CharacterUtility::s3988('info:ofi/fmt:kev:mtx:book');
            $url_parts[] = 'rft.btitle=' . CharacterUtility::s3988($this->getTitle());
            $url_parts[] = 'rft.genre=report';
        }

        $url_parts[] = 'rft.pub=' . CharacterUtility::s3988($this->getPublisher());

        // referent
        if ($this->hasField('url')) {
            $url_parts[] = 'rft_id=' . CharacterUtility::s3988($this->getField('url'));
        } elseif ($this->hasField('doi')) {
            $url_parts[] = 'rft_id=' . CharacterUtility::s3988('info:doi/' . $this->getField('doi'));
        }

        // referrer, the id of a collection of objects
        // see also http://www.openurl.info/registry/docs/pdf/info-sid.pdf
        $url_parts[] = 'rfr_id=' . CharacterUtility::s3988('info:sid/' . @$_SERVER['HTTP_HOST'] . ':' . basename(@$_GET[Configuration::Q_FILE]));

        $url_parts[] = 'rft.date=' . CharacterUtility::s3988($this->getYear());

        foreach ($this->getFormattedAuthorsArray() as $au) {
            $url_parts[] = 'rft.au=' . CharacterUtility::s3988($au);
        }


        return '<span class="Z3988" title="' . implode('&amp;', $url_parts) . '"></span>';
    }

    /** Returns an anchor for this entry.  */
    public function anchor()
    {
        return '<a class="bibanchor" name="' . $this->getRawAbbrv() . '"></a>';
    }

    /**
     * rebuild the set of constants used if any as a string
     */
    public function getConstants()
    {
        $result = '';
        foreach ($this->constants as $k => $v) {
            $result .= '@string{' . $k . '="' . $v . "\"}\n";
        }

        return $result;
    }

    /**
     * Displays a <pre> text of the given bib entry.
     * URLs are replaced by HTML links.
     */
    public function toEntryUnformatted()
    {
        $result = '';
        $result .= '<pre class="purebibtex">'; // pre is nice when it is embedded with no CSS available
        $entry = htmlspecialchars($this->getFullText(), ENT_NOQUOTES | ENT_XHTML, Configuration::OUTPUT_ENCODING);

        // Fields that should be hyperlinks
        // the order matters
        $hyperlinks = [
            'url' => '%O',
            'file' => '%O',
            'pdf' => '%O',
            'doi' => 'https://doi.org/%O',
            'gsid' => 'https://scholar.google.com/scholar?cites=%O'
        ];

        $vals = [];
        foreach ($hyperlinks as $field => $url) {
            if ($this->hasField($field)) {
                $href = str_replace('%O', $this->getField($field), $url);
                // this is not a parsing but a simple replacement
                $entry = str_replace($this->getField($field), '___' . $field . '___', $entry);
                $vals[$field] = $href;
            }
        }

        foreach ($vals as $field => $href) {
            if ($this->hasField($field)) {
                // this is not a parsing but a simple replacement
                $entry = str_replace(
                    '___' . $field . '___',
                    '<a' . get_target() . ' href="' . $href . '">' . $this->getField($field) . '</a>',
                    $entry
                );
            }
        }

        $result .= $entry;
        $result .= '</pre>';
        return $result;
    }

    /**
     * Gets the raw text of the entry (crossref + strings + entry)
     */
    public function getFullText()
    {
        $s = '';
        // adding the crossref if necessary
        if ($this->crossref != null) {
            $s .= $this->crossref->getFullText() . "\n";
        }

        $s .= $this->getConstants();
        $s .= $this->getText();
        return $s;
    }

    /** returns the first and last page of the entry as an array ([0]->first,  [2]->last) */
    public function getPages()
    {
        preg_match('#(\d+).*?(\d+)#', $this->getField('pages'), $matches);
        array_shift($matches);
        return $matches;
    }

    /** returns in the citation file format, tailored for github */
    public function toCFF()
    {
        $result = '';
        $result .= 'cff-version: 1.2.0' . "\n";
        $result .= '# CITATION.cff created with https://github.com/monperrus/bibtexbrowser/' . "\n";
        $result .= 'preferred-citation:' . "\n";
        $result .= '  title: "' . $this->getTitle() . '"' . "\n";
        if ($this->hasField('doi')) {
            $result .= '  doi: "' . $this->getField('doi') . '"' . "\n";
        }

        if ($this->hasField('year')) {
            $result .= '  year: "' . $this->getField('year') . '"' . "\n";
        }

        if ($this->hasField('journal')) {
            $result .= "  type: article\n";
            $result .= '  journal: "' . $this->getField('journal') . '"' . "\n";
        }

        if ($this->hasField('booktitle')) {
            $result .= "  type: conference-paper\n";
            $result .= '  conference: "' . $this->getField('booktitle') . '"' . "\n";
        }

        $result .= '  authors:' . "\n";
        foreach ($this->getFormattedAuthorsArray() as $author) {
            $split = splitFullName($author);
            $result .= '    - family-names: ' . $split[1] . "\n";
            $result .= '      given-names: ' . $split[0] . "\n";
        }

        return $result;
    }
}
