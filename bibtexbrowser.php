<?php /* bibtexbrowser: publication lists with bibtex and PHP
<!--this is version from commit __GITHUB__ -->
URL: http://www.monperrus.net/martin/bibtexbrowser/
Questions & Bug Reports: https://github.com/monperrus/bibtexbrowser/issues

(C) 2012-2020 Github contributors
(C) 2006-2020 Martin Monperrus
(C) 2014 Markus Jochim
(C) 2013 Matthieu Guillaumin
(C) 2005-2006 The University of Texas at El Paso / Joel Garcia, Leonardo Ruiz, and Yoonsik Cheon
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License as
published by the Free Software Foundation; either version 2 of the
License, or (at your option) any later version.

*/

use BibtexBrowser\BibtexBrowser\BibEntry;
use BibtexBrowser\BibtexBrowser\Utility\InternationalizationUtility;
use BibtexBrowser\BibtexBrowser\Utility\TemplateUtility;

require(__DIR__ . '/vendor/autoload.php');

// it is be possible to include( 'bibtexbrowser.php' ); several times in the same script
// added on Wednesday, June 01 2011, bug found by Carlos Bras
if (!defined('BIBTEXBROWSER')) {
    // this if block ends at the very end of this file, after all class and function declarations.
    define('BIBTEXBROWSER', 'v__GITHUB__');

    // support for configuration
    // set with bibtexbrowser_configure, get with config_value
    // you may have bibtexbrowser_configure('foo', 'bar') in bibtexbrowser.local.php
    global $CONFIGURATION;
    $CONFIGURATION = [];
    function bibtexbrowser_configure($key, $value)
    {
        global $CONFIGURATION;
        $CONFIGURATION[$key] = $value;
        if (!defined($key)) {
            define($key, $value);
        }
    }

    /**
     * @throws Exception
     */
    function bibtexbrowser_configuration($key)
    {
        global $CONFIGURATION;
        if (isset($CONFIGURATION[$key])) {
            return $CONFIGURATION[$key];
        }

        if (defined($key)) {
            return constant($key);
        }

        throw new Exception('no such configuration parameter: ' . $key);
    }

    /**
     * @throws Exception
     */
    function c($key)
    {
        return bibtexbrowser_configuration($key);
    }

    // *************** CONFIGURATION
    // I recommend to put your changes in bibtexbrowser.local.php
    // it will help you to upgrade the script with a new version
    // the changes that require existing bibtexbrowser symbols should be in bibtexbrowser.after.php (included at the end of this file)
    // per bibtex file configuration
    @include(__DIR__ . '/bibtexbrowser.local.php');
    @include(preg_replace('#\.php$#', '.local.php', __FILE__));

    // the encoding of your bibtex file
    @define(
        'BIBTEX_INPUT_ENCODING',
        'UTF-8'
    );//@define('BIBTEX_INPUT_ENCODING','iso-8859-1');//define('BIBTEX_INPUT_ENCODING','windows-1252');
    // the encoding of the HTML output
    @define('OUTPUT_ENCODING', 'UTF-8');

    // print a warning if deprecated variable is used
    if (defined('ENCODING')) {
        echo 'ENCODING has been replaced by BIBTEX_INPUT_ENCODING and OUTPUT_ENCODING';
    }

    // number of bib items per page
    // we use the same parameter 'num' as Google
    @define('PAGE_SIZE', isset($_GET['num']) ? (preg_match('#^\d+$#', $_GET['num']) ? $_GET['num'] : 10000) : 14);

    // bibtexbrowser uses a small piece of Javascript to improve the user experience
    // see http://en.wikipedia.org/wiki/Progressive_enhancement
    // if you don't like it, you can be disable it by adding in bibtexbrowser.local.php
    // @define('BIBTEXBROWSER_USE_PROGRESSIVE_ENHANCEMENT',false);
    @define('BIBTEXBROWSER_USE_PROGRESSIVE_ENHANCEMENT', true);
    @define('BIBLIOGRAPHYSTYLE', 'DefaultBibliographyStyle');// this is the name of a function
@define('BIBLIOGRAPHYSECTIONS', 'DefaultBibliographySections');// this is the name of a function
@define('BIBLIOGRAPHYTITLE', 'DefaultBibliographyTitle');// this is the name of a function

// shall we load MathJax to render math in $…$ in HTML?
    @define('BIBTEXBROWSER_RENDER_MATH', true);
    @define('MATHJAX_URI', '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/config/TeX-AMS_HTML.js?V=2.7.1');

    // the default jquery URI
    @define('JQUERY_URI', '//code.jquery.com/jquery-1.5.1.min.js');

    // can we load bibtex files on external servers?
    @define('BIBTEXBROWSER_LOCAL_BIB_ONLY', true);

    // the default view in {SimpleDisplay,AcademicDisplay,RSSDisplay,BibtexDisplay}
    @define('BIBTEXBROWSER_DEFAULT_DISPLAY', \BibtexBrowser\BibtexBrowser\SimpleDisplay::class);

    // the default template
    @define('BIBTEXBROWSER_DEFAULT_TEMPLATE', 'HTMLTemplate');

    // the target frame of menu links
    @define(
        'BIBTEXBROWSER_MENU_TARGET',
        'main'
    ); // might be define('BIBTEXBROWSER_MENU_TARGET','_self'); in bibtexbrowser.local.php

    @define('ABBRV_TYPE', 'index');// may be year/x-abbrv/key/none/index/keys-index

    // are robots allowed to crawl and index bibtexbrowser generated pages?
    @define('BIBTEXBROWSER_ROBOTS_NOINDEX', false);

    //the default view in the "main" (right hand side) frame
@define('BIBTEXBROWSER_DEFAULT_FRAME', 'year=latest'); // year=latest,all and all valid bibtexbrowser queries

// Wrapper to use when we are included by another script
    @define('BIBTEXBROWSER_EMBEDDED_WRAPPER', 'NoWrapper');

    // Main class to use
    @define('BIBTEXBROWSER_MAIN', \BibtexBrowser\BibtexBrowser\Dispatcher::class);

    // default order functions
    // Contract Returns < 0 if str1 is less than str2; > 0 if str1 is greater than str2, and 0 if they are equal.
    // can be @define('ORDER_FUNCTION','compare_bib_entry_by_title');
    // can be @define('ORDER_FUNCTION','compare_bib_entry_by_bibtex_order');
    @define('ORDER_FUNCTION', 'compare_bib_entry_by_year');
    @define('ORDER_FUNCTION_FINE', 'compare_bib_entry_by_month');

    // only displaying the n newest entries
    @define('BIBTEXBROWSER_NEWEST', 5);

    @define('BIBTEXBROWSER_NO_DEFAULT', false);

    // BIBTEXBROWSER_LINK_STYLE defines which function to use to display the links of a bibtex entry
@define('BIBTEXBROWSER_LINK_STYLE', 'bib2links_default'); // can be 'nothing' (a function that does nothing)

// do we add [bibtex] links ?
    @define('BIBTEXBROWSER_BIBTEX_LINKS', true);
    // do we add [pdf] links ?
    // if the file extention is not .pdf, the field name (pdf, url, or file) is used instead
    @define('BIBTEXBROWSER_PDF_LINKS', true);
    // do we add [doi] links ?
    @define('BIBTEXBROWSER_DOI_LINKS', true);
    // do we add [gsid] links (Google Scholar)?
    @define('BIBTEXBROWSER_GSID_LINKS', true);

    // should pdf, doi, url, gsid links be opened in a new window?
@define('BIBTEXBROWSER_LINKS_TARGET', '_self');// can be _blank (new window), _top (with frames)

// should authors be linked to [none/homepage/resultpage]
    // none: nothing
    // their homepage if defined as @strings
    // their publication lists according to this bibtex
    @define('BIBTEXBROWSER_AUTHOR_LINKS', 'homepage');

    // BIBTEXBROWSER_LAYOUT defines the HTML rendering layout of the produced HTML
    // may be table/list/ordered_list/definition/none (for <table>, <ol>, <dl>, nothing resp.).
    // for list/ordered_list, the abbrevations are not taken into account (see ABBRV_TYPE)
    // for ordered_list, the index is given by HTML directly (in increasing order)
    @define('BIBTEXBROWSER_LAYOUT', 'table');

    // should the original bibtex be displayed or a reconstructed one with filtering
    // values: original/reconstructed
    // warning, with reconstructed, the latex markup for accents/diacritics is lost
    @define('BIBTEXBROWSER_BIBTEX_VIEW', 'original');
    // a list of fields that will not be shown in the bibtex view if BIBTEXBROWSER_BIBTEX_VIEW=reconstructed
    @define('BIBTEXBROWSER_BIBTEX_VIEW_FILTEREDOUT', 'comment|note|file');

    // should Latex macros be executed (e.g. \'e -> é
    @define('BIBTEXBROWSER_USE_LATEX2HTML', true);

    // Which is the first html <hN> level that should be used in embedded mode?
    @define('BIBTEXBROWSER_HTMLHEADINGLEVEL', 2);

    @define('BIBTEXBROWSER_ACADEMIC_TOC', false);

    @define('BIBTEXBROWSER_DEBUG', false);

    // how to print authors names?
// default => as in the bibtex file
// USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT = true => "Meyer, Herbert"
// USE_INITIALS_FOR_NAMES = true => "Meyer H"
// USE_FIRST_THEN_LAST => Herbert Meyer
@define('USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT', false);// output authors in a comma separated form, e.g. "Meyer, H"?
@define('USE_INITIALS_FOR_NAMES', false); // use only initials for all first names?
@define('USE_FIRST_THEN_LAST', false); // put first names before last names?
@define(
    'FORCE_NAMELIST_SEPARATOR',
    ''
); // if non-empty, use this to separate multiple names regardless of USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT
    @define('LAST_AUTHOR_SEPARATOR', ' and ');
    @define(
        'USE_OXFORD_COMMA',
        false
    ); // adds an additional separator in addition to LAST_AUTHOR_SEPARATOR if there are more than two authors

    @define('TYPES_SIZE', 10); // number of entry types per table
@define('YEAR_SIZE', 20); // number of years per table
@define('AUTHORS_SIZE', 30); // number of authors per table
@define('TAGS_SIZE', 30); // number of keywords per table
@define('READLINE_LIMIT', 1024);
    @define('Q_YEAR', 'year');
    @define('Q_YEAR_PAGE', 'year_page');
    @define('Q_YEAR_INPRESS', 'in press');
    @define('Q_YEAR_ACCEPTED', 'accepted');
    @define('Q_YEAR_SUBMITTED', 'submitted');
    @define('Q_FILE', 'bib');
    @define('Q_AUTHOR', 'author');
    @define('Q_AUTHOR_PAGE', 'author_page');
    @define('Q_TAG', 'keywords');
    @define('Q_TAG_PAGE', 'keywords_page');
    @define('Q_TYPE', 'type');// used for queries
    @define('Q_TYPE_PAGE', 'type_page');
    @define('Q_ALL', 'all');
    @define('Q_ENTRY', 'entry');
    @define('Q_KEY', 'key');
    @define('Q_KEYS', 'keys'); // filter entries using a url-encoded, JSON-encoded array of bibtex keys
    @define('Q_SEARCH', 'search');
    @define('Q_EXCLUDE', 'exclude');
    @define('Q_RESULT', 'result');
    @define('Q_ACADEMIC', 'academic');
    @define('Q_DB', 'bibdb');
    @define('Q_LATEST', 'latest');
    @define('Q_RANGE', 'range');
    @define('AUTHOR', 'author');
    @define('EDITOR', 'editor');
    @define('SCHOOL', 'school');
    @define('TITLE', 'title');
    @define('BOOKTITLE', 'booktitle');
    @define('YEAR', 'year');
    @define('MULTIPLE_BIB_SEPARATOR', ';');
    @define('METADATA_COINS', true); // see https://en.wikipedia.org/wiki/COinS
    @define(
        'METADATA_GS',
        false
    ); // metadata google scholar, see http://www.monperrus.net/martin/accurate+bibliographic+metadata+and+google+scholar
@define('METADATA_DC', true); // see http://dublincore.org/
@define('METADATA_OPENGRAPH', true);  // see http://ogp.me/
@define('METADATA_EPRINTS', false); // see https://wiki.eprints.org/w/Category:EPrints_Metadata_Fields

// define sort order for special values in 'year' field
    // highest number is sorted first
    // don't exceed 0 though, since the values are added to PHP_INT_MAX
    @define('ORDER_YEAR_INPRESS', -0);
    @define('ORDER_YEAR_ACCEPTED', -1);
    @define('ORDER_YEAR_SUBMITTED', -2);
    @define('ORDER_YEAR_OTHERNONINT', -3);


    // in embedded mode, we still need a URL for displaying bibtex entries alone
    // this is usually resolved to bibtexbrowser.php
    // but can be overridden in bibtexbrowser.local.php
    // for instance with @define('BIBTEXBROWSER_URL',''); // links to the current page with ?
    @define('BIBTEXBROWSER_URL', basename(__FILE__));

    // Specify the location of the cache file for servers that need temporary files written in a specific location
    @define('CACHE_DIR', '');

    // Specify the location of the bib file for servers that need do not allow slashes in URLs,
    // where the bib file and bibtexbrowser.php are in different directories.
    @define('DATA_DIR', '');

    // *************** END CONFIGURATION

    define('Q_INNER_AUTHOR', '_author');// internally used for representing the author
@define('Q_INNER_KEYS_INDEX', '_keys-index');// used for storing indices in $_GET[Q_KEYS] array

// for clean search engine links
    // we disable url rewriting
    // ... and hope that your php configuration will accept one of these
    @ini_set('session.use_only_cookies', 1);
    @ini_set('session.use_trans_sid', 0);
    @ini_set('url_rewriter.tags', '');

    function nothing()
    {
    }

    function config_value($key)
    {
        global $CONFIGURATION;
        if (isset($CONFIGURATION[$key])) {
            return $CONFIGURATION[$key];
        }

        if (defined($key)) {
            return constant($key);
        }

        die('no such configuration: ' . $key);
    }

    /**
     * parses $_GET[Q_FILE] and puts the result (an object of type BibDataBase) in $_GET[Q_DB].
     * See also zetDB().
     */
    function setDB()
    {
        [$db, $parsed, $updated, $saved] = _zetDB(@$_GET[Q_FILE]);
        $_GET[Q_DB] = $db;
        return $updated;
    }

    /**
     * parses the $bibtex_filenames (usually semi-column separated) and returns an object of type BibDataBase.
     * See also setDB()
     */
    function zetDB($bibtex_filenames)
    {
        [$db, $parsed, $updated, $saved] = _zetDB($bibtex_filenames);
        return $db;
    }

    /** @nodoc */
    function default_message()
    {
        if (config_value('BIBTEXBROWSER_NO_DEFAULT') == true) {
            return;
        } ?>
<div id="bibtexbrowser_message">
    Congratulations! bibtexbrowser is correctly installed!<br/>
    Now you have to pass the name of the bibtex file as parameter (e.g. bibtexbrowser.php?bib=mybib.php)<br/>
    You may browse:<br/>
    <?php
    foreach (glob('*.bib') as $bibfile) {
        $url = '?bib=' . $bibfile;
        echo '<a href="' . $url . '" rel="nofollow">' . $bibfile . '</a><br/>';
    }

        echo '</div>';
    }

    /**
     * returns the target of links
     */
    function get_target(): string
    {
        if (c('BIBTEXBROWSER_LINKS_TARGET') !== '_self') {
            return ' target="' . c('BIBTEXBROWSER_LINKS_TARGET') . '"';
        }

        return '';
    }

    /** @nodoc */
    function _zetDB(?string $bibtex_filenames)
    {
        $db = null;

        // default bib file, if no file is specified in the query string.
        if (!isset($bibtex_filenames) || $bibtex_filenames === '') {
            default_message();
            exit;
        }

        // first does the bibfiles exist:
        // $bibtex_filenames can be urlencoded for instance if they contain slashes
        // so we decode it
        $bibtex_filenames = urldecode($bibtex_filenames);

        // ---------------------------- HANDLING unexistent files
        foreach (explode(MULTIPLE_BIB_SEPARATOR, $bibtex_filenames) as $bib) {

            // get file extension to only allow .bib files
            $ext = pathinfo($bib, PATHINFO_EXTENSION);
            // this is a security protection
            if (BIBTEXBROWSER_LOCAL_BIB_ONLY && (!file_exists(DATA_DIR . $bib) || strcasecmp($ext, 'bib') != 0)) {
                // to automate dectection of faulty links with tools such as webcheck
                header('HTTP/1.1 404 Not found');
                // escape $bib to prevent XSS
                $escapedBib = htmlEntities($bib, ENT_QUOTES);
                die('<b>the bib file ' . $escapedBib . ' does not exist !</b>');
            }
        }

        // ---------------------------- HANDLING HTTP If-modified-since
        // testing with $ curl -v --header "If-Modified-Since: Fri, 23 Oct 2010 19:22:47 GMT" "... bibtexbrowser.php?key=wasylkowski07&bib=..%252Fstrings.bib%253B..%252Fentries.bib"
        // and $ curl -v --header "If-Modified-Since: Fri, 23 Oct 2000 19:22:47 GMT" "... bibtexbrowser.php?key=wasylkowski07&bib=..%252Fstrings.bib%253B..%252Fentries.bib"

        // save bandwidth and server cpu
        // (imagine the number of requests from search engine bots...)
        $bib_is_unmodified = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        foreach (explode(MULTIPLE_BIB_SEPARATOR, $bibtex_filenames) as $bib) {
            $bib_is_unmodified =
                $bib_is_unmodified
                && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) > filemtime($bib));
        }

        if ($bib_is_unmodified && !headers_sent()) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }


        // ---------------------------- HANDLING caching of compiled bibtex files
        // for sake of performance, once the bibtex file is parsed
        // we try to save a "compiled" in a txt file
        $compiledbib = CACHE_DIR . 'bibtexbrowser_' . md5($bibtex_filenames) . '.dat';

        $parse = filemtime(__FILE__) > @filemtime($compiledbib);

        // do we have a compiled version ?
        if (is_file($compiledbib)
            && is_readable($compiledbib)
            && filesize($compiledbib) > 0
        ) {
            $f = fopen($compiledbib, 'rb+'); // some Unix seem to consider flock as a writing operation
            //we use a lock to avoid that a call to bibbtexbrowser made while we write the object loads an incorrect object
            if (flock($f, LOCK_EX)) {
                $s = filesize($compiledbib);
                $ser = fread($f, $s);
                $db = @unserialize($ser);
                flock($f, LOCK_UN);
            } else {
                die('could not get the lock');
            }

            fclose($f);
            // basic test
            // do we have an correct version of the file
            if (!is_a($db, 'BibDataBase')) {
                unlink($compiledbib);
                if (BIBTEXBROWSER_DEBUG) {
                    die('$db not a BibDataBase. please reload.');
                }

                $parse = true;
            }
        } else {
            $parse = true;
        }

        // we don't have a compiled version
        if ($parse) {
            // then parsing the file
            $db = new \BibtexBrowser\BibtexBrowser\BibDataBase();
            foreach (explode(MULTIPLE_BIB_SEPARATOR, $bibtex_filenames) as $bib) {
                $db->load($bib);
            }
        }

        $updated = false;
        // now we may update the database
        if (!file_exists($compiledbib)) {
            @touch($compiledbib);
            $updated = true; // limit case
        } else {
            foreach (explode(MULTIPLE_BIB_SEPARATOR, $bibtex_filenames) as $bib) {
                // is it up to date ? wrt to the bib file and the script
                // then upgrading with a new version of bibtexbrowser triggers a new compilation of the bib file
                if (filemtime($bib) > filemtime($compiledbib) || filemtime(__FILE__) > filemtime($compiledbib)) {
                    $db->update($bib);
                    $updated = true;
                }
            }
        }

        $saved = false;
        // are we able to save the compiled version ?
        // note that the compiled version is saved in the current working directory
        if (($parse || $updated) && is_writable($compiledbib)) {
            // we use 'a' because the file is not locked between fopen and flock
            $f = fopen($compiledbib, 'ab');
            //we use a lock to avoid that a call to bibbtexbrowser made while we write the object loads an incorrect object
            if (flock($f, LOCK_EX)) {
                ftruncate($f, 0);
                fwrite($f, serialize($db));
                flock($f, LOCK_UN);
                $saved = true;
            } else {
                die('could not get the lock');
            }

            fclose($f);
        }

        return [$db, $parse, $updated, $saved];
    }

    /**
     * returns a collection of links for the given bibtex entry
     *  e.g. [bibtex] [doi][pdf]
     */
    function bib2links_default(BibEntry $bibentry): string
    {
        $links = [];

        if (BIBTEXBROWSER_BIBTEX_LINKS) {
            $link = $bibentry->getBibLink();
            if ($link !== '') {
                $links[] = $link;
            };
        }

        if (BIBTEXBROWSER_PDF_LINKS) {
            $link = $bibentry->getUrlLink();
            if ($link !== '') {
                $links[] = $link;
            };
        }

        if (BIBTEXBROWSER_DOI_LINKS) {
            $link = $bibentry->getDoiLink();
            if ($link !== '') {
                $links[] = $link;
            };
        }

        if (BIBTEXBROWSER_GSID_LINKS) {
            $link = $bibentry->getGSLink();
            if ($link !== '') {
                $links[] = $link;
            };
        }

        return '<span class="bibmenu">' . implode(' ', $links) . '</span>';
    }


    /**
     * this function encapsulates the user-defined name for bib to HTML
     */
    function bib2html($bibentry)
    {
        $function = bibtexbrowser_configuration('BIBLIOGRAPHYSTYLE');
        return $function($bibentry);
    }

    /**
     * this function encapsulates the user-defined name for bib2links
     */
    function bib2links($bibentry)
    {
        $function = c('BIBTEXBROWSER_LINK_STYLE');
        return $function($bibentry);
    }

    /**
     * encapsulates the user-defined sections. @nodoc
     */
    function _DefaultBibliographySections()
    {
        $function = BIBLIOGRAPHYSECTIONS;
        return $function();
    }

    /**
     * encapsulates the user-defined sections. @nodoc
     */
    function _DefaultBibliographyTitle($query)
    {
        $function = BIBLIOGRAPHYTITLE;
        return $function($query);
    }

    function DefaultBibliographyTitle(array $query): string
    {
        $result = 'Publications in ' . $_GET[Q_FILE];
        if (isset($query['all'])) {
            unset($query['all']);
        }

        if ($query !== []) {
            $result .= ' - ' . query2title($query);
        }

        return $result;
    }

    /**
     * compares two instances of BibEntry by modification time
     */
    function compare_bib_entry_by_mtime(BibEntry $a, BibEntry $b)
    {
        return -($a->getTimestamp() - $b->getTimestamp());
    }

    /**
     * compares two instances of BibEntry by order in Bibtex file
     */
    function compare_bib_entry_by_bibtex_order(BibEntry $a, BibEntry $b): int
    {
        return $a->order - $b->order;
    }

    /**
     * compares two instances of BibEntry by year
     */
    function compare_bib_entry_by_year(BibEntry $a, BibEntry $b): int
    {
        $yearA = (int)$a->getYear(); // 0 if no year
        $yearB = (int)$b->getYear();

        if ($yearA === 0) {
            switch (strtolower($a->getYearRaw())) {
                case Q_YEAR_INPRESS:
                    $yearA = PHP_INT_MAX + ORDER_YEAR_INPRESS;
                    break;
                case Q_YEAR_ACCEPTED:
                    $yearA = PHP_INT_MAX + ORDER_YEAR_ACCEPTED;
                    break;
                case Q_YEAR_SUBMITTED:
                    $yearA = PHP_INT_MAX + ORDER_YEAR_SUBMITTED;
                    break;
                default:
                    $yearA = PHP_INT_MAX + ORDER_YEAR_OTHERNONINT;
            }
        }

        if ($yearB === 0) {
            switch (strtolower($b->getYearRaw())) {
                case Q_YEAR_INPRESS:
                    $yearB = PHP_INT_MAX + ORDER_YEAR_INPRESS;
                    break;
                case Q_YEAR_ACCEPTED:
                    $yearB = PHP_INT_MAX + ORDER_YEAR_ACCEPTED;
                    break;
                case Q_YEAR_SUBMITTED:
                    $yearB = PHP_INT_MAX + ORDER_YEAR_SUBMITTED;
                    break;
                default:
                    $yearB = PHP_INT_MAX + ORDER_YEAR_OTHERNONINT;
            }
        }

        if ($yearA === $yearB) {
            return 0;
        }

        if ($yearA > $yearB) {
            return -1;
        }

        return 1;
    }

    /**
     * compares two instances of BibEntry by title
     */
    function compare_bib_entry_by_title(BibEntry $a, BibEntry $b): int
    {
        return strcmp($a->getTitle(), $b->getTitle());
    }

    /**
     * compares two instances of BibEntry by undecorated Abbrv
     */
    function compare_bib_entry_by_raw_abbrv(BibEntry $a, BibEntry $b): int
    {
        return strcmp($a->getRawAbbrv(), $b->getRawAbbrv());
    }

    /**
     * compares two instances of BibEntry by author or editor
     */
    function compare_bib_entry_by_name(BibEntry $a, BibEntry $b): int
    {
        if ($a->hasField(AUTHOR)) {
            $namesA = $a->getAuthor();
        } elseif ($a->hasField(EDITOR)) {
            $namesA = $a->getField(EDITOR);
        } else {
            $namesA = InternationalizationUtility::translate('No author');
        }

        if ($b->hasField(AUTHOR)) {
            $namesB = $b->getAuthor();
        } elseif ($b->hasField(EDITOR)) {
            $namesB = $b->getField(EDITOR);
        } else {
            $namesB = InternationalizationUtility::translate('No author');
        }

        return strcmp($namesA, $namesB);
    }

    /** compares two instances of BibEntry by month
     * @author Jan Geldmacher
     */
    function compare_bib_entry_by_month(BibEntry $a, BibEntry $b)
    {
        //bibkey which is used for sorting
        $sort_key = 'month';
        //desired order of values
        $sort_order_values = [
            'jan',
            'january',
            'feb',
            'february',
            'mar',
            'march',
            'apr',
            'april',
            'may',
            'jun',
            'june',
            'jul',
            'july',
            'aug',
            'august',
            'sep',
            'september',
            'oct',
            'october',
            'nov',
            'november',
            'dec',
            'december'
        ];
        //order: 1=as specified in $sort_order_values  or -1=reversed
        $order = -1;

        //first check if the search key exists
        if (!array_key_exists($sort_key, $a->fields) && !array_key_exists($sort_key, $b->fields)) {
            //neither a nor b have the key -> we compare the keys
            $retval = strcmp($a->getKey(), $b->getKey());
        } elseif (!array_key_exists($sort_key, $a->fields)) {
            //only b has the field -> b is greater
            $retval = -1;
        } elseif (!array_key_exists($sort_key, $b->fields)) {
            //only a has the key -> a is greater
            $retval = 1;
        } else {
            //both have the key, sort using the order given in $sort_order_values

            $val_a = array_search(strtolower($a->fields[$sort_key]), $sort_order_values);
            $val_b = array_search(strtolower($b->fields[$sort_key]), $sort_order_values);

            if (($val_a === false && $val_b === false) || ($val_a === $val_b)) {
                //neither a nor b are in the search array or a=b -> both are equal
                $retval = 0;
            } elseif (($val_a === false) || ($val_a < $val_b)) {
                //a is not in the search array or a<b -> b is greater
                $retval = -1;
            } elseif (($val_b === false) || (($val_a > $val_b))) {
                //b is not in the search array or a>b -> a is greater
                $retval = 1;
            }
        }

        return $order * $retval;
    }

    /** is the default sectioning for AcademicDisplay (books, articles, proceedings, etc. ) */
    function DefaultBibliographySections(): array
    {
        return
            [
                // Books
                [
                    'query' => [Q_TYPE => 'book|proceedings'],
                    'title' => InternationalizationUtility::translate('Books')
                ],
                // Book chapters
                [
                    'query' => [Q_TYPE => 'incollection|inbook'],
                    'title' => InternationalizationUtility::translate('Book Chapters')
                ],
                // Journal / Bookchapters
                [
                    'query' => [Q_TYPE => 'article'],
                    'title' => InternationalizationUtility::translate('Refereed Articles')
                ],
                // conference papers
                [
                    'query' => [Q_TYPE => 'inproceedings|conference', Q_EXCLUDE => 'workshop'],
                    'title' => InternationalizationUtility::translate('Refereed Conference Papers')
                ],
                // workshop papers
                [
                    'query' => [Q_TYPE => 'inproceedings', Q_SEARCH => 'workshop'],
                    'title' => InternationalizationUtility::translate('Refereed Workshop Papers')
                ],
                // misc and thesis
                [
                    'query' => [Q_TYPE => 'misc|phdthesis|mastersthesis|bachelorsthesis|techreport'],
                    'title' => InternationalizationUtility::translate('Other Publications')
                ]
            ];
    }


    /** transforms a $bibentry into an HTML string.
     * It is called by function bib2html if the user did not choose a specific style
     * the default usable CSS styles are
     * .bibtitle { font-weight:bold; }
     * .bibbooktitle { font-style:italic; }
     * .bibauthor { }
     * .bibpublisher { }
     *
     * See https://schema.org/ScholarlyArticle for the metadata
     */
    function DefaultBibliographyStyle(BibEntry $bibentry): string
    {
        $title = $bibentry->getTitle();
        $type = $bibentry->getType();

        // later on, all values of $entry will be joined by a comma
        $entry = [];

        // title
        // usually in bold: .bibtitle { font-weight:bold; }
        $title = '<span class="bibtitle"  itemprop="name">' . $title . '</span>';
        if ($bibentry->hasField('url')) {
            $title = ' <a' . get_target() . ' href="' . $bibentry->getField('url') . '">' . $title . '</a>';
        }

        $coreInfo = $title;

        // adding author info
        if ($bibentry->hasField('author')) {
            $coreInfo .= ' (<span class="bibauthor">';

            $authors = [];
            foreach ($bibentry->getFormattedAuthorsArray() as $a) {
                $authors[] = '<span itemprop="author" itemtype="http://schema.org/Person">' . $a . '</span>';
            }

            $coreInfo .= $bibentry->implodeAuthors($authors);

            $coreInfo .= '</span>)';
        }

        // core info usually contains title + author
        $entry[] = $coreInfo;

        // now the book title
        $booktitle = '';
        if ($type === 'inproceedings') {
            $booktitle = InternationalizationUtility::translate('In') . ' ' . '<span itemprop="isPartOf">' . $bibentry->getField(BOOKTITLE) . '</span>';
        }

        if ($type === 'incollection') {
            $booktitle = InternationalizationUtility::translate('Chapter in') . ' ' . '<span itemprop="isPartOf">' . $bibentry->getField(BOOKTITLE) . '</span>';
        }

        if ($type === 'inbook') {
            $booktitle = InternationalizationUtility::translate('Chapter in') . ' ' . $bibentry->getField('chapter');
        }

        if ($type === 'article') {
            $booktitle = InternationalizationUtility::translate('In') . ' ' . '<span itemprop="isPartOf">' . $bibentry->getField('journal') . '</span>';
        }

        //// we may add the editor names to the booktitle
        $editor = '';
        if ($bibentry->hasField(EDITOR)) {
            $editor = $bibentry->getFormattedEditors();
        }

        if ($editor != '') {
            $booktitle .= ' (' . $editor . ')';
        }

        // end editor section

        // is the booktitle available
        if ($booktitle != '') {
            $entry[] = '<span class="bibbooktitle">' . $booktitle . '</span>';
        }


        $publisher = '';
        if ($type === 'phdthesis') {
            $publisher = InternationalizationUtility::translate('PhD thesis') . ', ' . $bibentry->getField(SCHOOL);
        }

        if ($type === 'mastersthesis') {
            $publisher = InternationalizationUtility::translate("Master's thesis") . ', ' . $bibentry->getField(SCHOOL);
        }

        if ($type === 'bachelorsthesis') {
            $publisher = InternationalizationUtility::translate("Bachelor's thesis") . ', ' . $bibentry->getField(SCHOOL);
        }

        if ($type === 'techreport') {
            $publisher = InternationalizationUtility::translate('Technical report');
            if ($bibentry->hasField('number')) {
                $publisher .= ' ' . $bibentry->getField('number');
            }

            $publisher .= ', ' . $bibentry->getField('institution');
        }

        if ($type === 'misc') {
            $publisher = $bibentry->getField('howpublished');
        }

        if ($bibentry->hasField('publisher')) {
            $publisher = $bibentry->getField('publisher');
        }

        if ($publisher != '') {
            $entry[] = '<span class="bibpublisher">' . $publisher . '</span>';
        }


        if ($bibentry->hasField('volume')) {
            $entry[] = InternationalizationUtility::translate('volume') . ' ' . $bibentry->getField('volume');
        }


        if ($bibentry->hasField(YEAR)) {
            $entry[] = '<span itemprop="datePublished">' . $bibentry->getYear() . '</span>';
        }

        $result = implode(', ', $entry) . '.';

        // add the Coin URL
        $result .= $bibentry->toCoins();

        return '<span itemscope="" itemtype="http://schema.org/ScholarlyArticle">' . $result . '</span>';
    }


    /** is the Bibtexbrowser style contributed by Janos Tapolcai. It looks like the IEEE transaction style.
     * usage:
     * Add the following line in "bibtexbrowser.local.php"
     * <pre>
     * @define('BIBLIOGRAPHYSTYLE','JanosBibliographyStyle');
     * </pre>
     */
    function JanosBibliographyStyle(BibEntry $bibentry): string
    {
        $title = $bibentry->getTitle();
        $type = $bibentry->getType();

        $entry = [];

        // author
        if ($bibentry->hasField('author')) {
            $entry[] = '<span class="bibauthor">' . $bibentry->getFormattedAuthorsString() . '</span>';
        }

        // title
        $title = '"' . '<span class="bibtitle">' . $title . '</span>' . '"';
        if ($bibentry->hasField('url')) {
            $title = ' <a' . get_target() . ' href="' . $bibentry->getField('url') . '">' . $title . '</a>';
        }

        $entry[] = $title;


        // now the origin of the publication is in italic
        $booktitle = '';

        if (($type === 'misc') && $bibentry->hasField('note')) {
            $booktitle = $bibentry->getField('note');
        }

        if ($type === 'inproceedings' && $bibentry->hasField(BOOKTITLE)) {
            $booktitle = '<span class="bibbooktitle">' . 'In ' . $bibentry->getField(BOOKTITLE) . '</span>';
        }

        if ($type === 'incollection' && $bibentry->hasField(BOOKTITLE)) {
            $booktitle = '<span class="bibbooktitle">' . 'Chapter in ' . $bibentry->getField(BOOKTITLE) . '</span>';
        }

        if ($type === 'article' && $bibentry->hasField('journal')) {
            $booktitle = '<span class="bibbooktitle">' . 'In ' . $bibentry->getField('journal') . '</span>';
        }


        //// ******* EDITOR
        $editor = '';
        if ($bibentry->hasField(EDITOR)) {
            $editor = $bibentry->getFormattedEditors();
        }

        if ($booktitle != '') {
            if ($editor != '') {
                $booktitle .= ' (' . $editor . ')';
            }

            $entry[] = '<i>' . $booktitle . '</i>';
        }

        $publisher = '';
        if ($type === 'phdthesis') {
            $publisher = 'PhD thesis, ' . $bibentry->getField(SCHOOL);
        }

        if ($type === 'mastersthesis') {
            $publisher = "Master's thesis, " . $bibentry->getField(SCHOOL);
        }

        if ($type === 'techreport') {
            $publisher = 'Technical report, ';
            $publisher .= $bibentry->getField('institution');
            if ($bibentry->hasField('number')) {
                $publisher .= ' ' . $bibentry->getField('number');
            }
        }

        if ($bibentry->hasField('publisher')) {
            $publisher = $bibentry->getField('publisher');
        }

        if ($publisher != '') {
            $entry[] = $publisher;
        }

        if ($type === 'article') {
            if ($bibentry->hasField('volume')) {
                $entry[] = 'vol. ' . $bibentry->getField('volume');
            }

            if ($bibentry->hasField('number')) {
                $entry[] = 'no. ' . $bibentry->getField('number');
            }
        }

        if ($bibentry->hasField('address')) {
            $entry[] = $bibentry->getField('address');
        }

        if ($bibentry->hasField('pages')) {
            $entry[] = str_replace('--', '-', 'pp. ' . $bibentry->getField('pages'));
        }


        if ($bibentry->hasField(YEAR)) {
            $entry[] = $bibentry->getYear();
        }

        $result = implode(', ', $entry) . '.';

        // add the Coin URL
        $result .= "\n" . $bibentry->toCoins();

        return '<span itemscope="" itemtype="http://schema.org/ScholarlyArticle">' . $result . '</span>';
    }


    /** Bibtexbrowser style producing vancouver style often used in medicine.
     *
     *  See: Patrias K. Citing medicine: the NLM style guide for authors, editors,
     *  and publishers [Internet]. 2nd ed. Wendling DL, technical editor.
     *  Bethesda (MD): National Library of Medicine (US); 2007 -
     *  [updated 2011 Sep 15; cited 2015 April 18].
     *  Available from: http://www.nlm.nih.gov/citingmedicine
     *
     * usage: Add the following lines to "bibtexbrowser.local.php"
     * <pre>
     * @define('BIBLIOGRAPHYSTYLE','VancouverBibliographyStyle');
     * @define('USE_INITIALS_FOR_NAMES',true);
     * </pre>
     */
    function VancouverBibliographyStyle(BibEntry $bibentry): string
    {
        $title = $bibentry->getTitle();
        $type = $bibentry->getType();

        $entry = [];

        // author
        if ($bibentry->hasField('author')) {
            $entry[] = $bibentry->getFormattedAuthorsString() . '. ';
        }

        // Ensure punctuation mark at title's end
        if (rtrim($title) !== '' && strpos(':.;,?!', substr(rtrim($title), -1)) > 0) {
            $title .= ' ';
        } else {
            $title .= '. ';
        }

        if ($bibentry->hasField('url')) {
            $title = ' <a' . get_target() . ' href="' . $bibentry->getField('url') . '">' . $title . '</a>';
        }

        $entry[] = $title;

        $booktitle = '';

        //// ******* EDITOR
        $editor = '';
        if ($bibentry->hasField(EDITOR)) {
            $editor = $bibentry->getFormattedEditors() . ' ';
        }

        if (($type === 'misc') && $bibentry->hasField('note')) {
            $booktitle = $bibentry->getField('note');
        } elseif ($type === 'inproceedings') {
            $booktitle = 'In: ' . $editor . $bibentry->getField(BOOKTITLE);
        } elseif ($type === 'incollection') {
            $booktitle = 'Chapter in ';
            if ($editor !== '') {
                $booktitle .= $editor;
            }

            $booktitle .= $bibentry->getField(BOOKTITLE);
        } elseif ($type === 'article') {
            $booktitle = $bibentry->getField('journal');
        }

        if ($booktitle !== '') {
            $entry[] = $booktitle . '. ';
        }


        $publisher = '';
        if ($type === 'phdthesis') {
            $publisher = 'PhD thesis, ' . $bibentry->getField(SCHOOL);
        } elseif ($type === 'mastersthesis') {
            $publisher = "Master's thesis, " . $bibentry->getField(SCHOOL);
        } elseif ($type === 'techreport') {
            $publisher = 'Technical report, ' . $bibentry->getField('institution');
        }

        if ($bibentry->hasField('publisher')) {
            $publisher = $bibentry->getField('publisher');
        }

        if ($publisher !== '') {
            if ($bibentry->hasField('address')) {
                $entry[] = $bibentry->getField('address') . ': ';
            }

            $entry[] = $publisher . '; ';
        }


        if ($bibentry->hasField(YEAR)) {
            $entry[] = $bibentry->getYear();
        }

        if ($bibentry->hasField('volume')) {
            $entry[] = ';' . $bibentry->getField('volume');
        }

        if ($bibentry->hasField('number')) {
            $entry[] = '(' . $bibentry->getField('number') . ')';
        }

        if ($bibentry->hasField('pages')) {
            $entry[] = str_replace('--', '-', ':' . $bibentry->getField('pages'));
        }

        $result = implode('', $entry) . '.';

        // some comments (e.g. acceptance rate)?
        if ($bibentry->hasField('comment')) {
            $result .= ' (' . $bibentry->getField('comment') . ')';
        }

        // add the Coin URL
        $result .= "\n" . $bibentry->toCoins();

        return $result;
    }


    // ----------------------------------------------------------------------
    // DISPLAY MANAGEMENT
    // ----------------------------------------------------------------------
    /** orders two BibEntry as defined by ORDER_FUNCTION
     * (by default compares two instances of BibEntry by year and then month)
     */
    function compare_bib_entries(BibEntry $bib1, BibEntry $bib2)
    {
        $cmp = compare_bib_entry_by_year($bib1, $bib2);
        if ($cmp === 0) {
            $cmp = compare_bib_entry_by_month($bib1, $bib2);
        }

        return $cmp;
    }

    /**
     * creates a query string given an array of parameter, with all specifities of bibtexbrowser_ (such as adding the bibtex file name &bib=foo.bib
     */
    function createQueryString(array $array_param): string
    {
        // then a simple transformation and implode
        foreach ($array_param as $key => $val) {
            // the inverse transformation should also be implemented into query2title
            if ($key == Q_INNER_AUTHOR) {
                $key = Q_AUTHOR;
            }

            if ($key === BibEntry::Q_INNER_TYPE) {
                $key = Q_TYPE;
            }

            if ($key == Q_KEYS) {
                $val = urlencode(json_encode($val));
            }

            if ($key == Q_INNER_KEYS_INDEX) {
                continue;
            }

            $array_param[$key] = $key . '=' . urlencode($val);
        }

        // adding the bibtex file name is not already there
        if (isset($_GET[Q_FILE]) && !isset($array_param[Q_FILE])) {
            // first we add the name of the bib file
            $array_param[Q_FILE] = Q_FILE . '=' . urlencode($_GET[Q_FILE]);
        }

        return implode('&amp;', $array_param);
    }

    /**
     * returns a href string of the form: href="?bib=testing.bib&search=JML.
     * Based on createQueryString.
     * @nodoc
     */
    function makeHref(?array $query = null): string
    {
        return 'href="' . bibtexbrowser_configuration('BIBTEXBROWSER_URL') . '?' . createQueryString($query) . '"';
    }


    /** returns the splitted name of an author name as an array. The argument is assumed to be
     * "FirstName LastName" or "LastName, FirstName".
     */
    function splitFullName(string $author): array
    {
        $author = trim($author);
        // the author format is "Joe Dupont"
        if (strpos($author, ',') === false) {
            $parts = explode(' ', $author);
            // get the last name
            $lastname = array_pop($parts);
            $firstname = implode(' ', $parts);
        } // the author format is "Dupont, J."
        else {
            $parts = explode(',', $author);
            // get the last name
            $lastname = str_replace(',', '', array_shift($parts));
            $firstname = implode(' ', $parts);
        }

        return [trim($firstname), trim($lastname)];
    }

    if (!function_exists('poweredby')) {
        /** Returns the powered by part. @nodoc */
        function poweredby(): string
        {
            $poweredby = "\n" . '<div style="text-align:right;font-size: xx-small;opacity: 0.6;" class="poweredby">';
            $poweredby .= '<!-- If you like bibtexbrowser, thanks to keep the link :-) -->';
            $poweredby .= 'Powered by <a href="http://www.monperrus.net/martin/bibtexbrowser/">bibtexbrowser</a><!--v__GITHUB__-->';
            $poweredby .= '</div>' . "\n";
            return $poweredby;
        }
    }

    if (!function_exists('bibtexbrowser_top_banner')) {
        function bibtexbrowser_top_banner(): string
        {
            return '';
        }
    }

    if (!function_exists('javascript_math')) {
        function javascript_math()
        {
            ?>
            <script type="text/x-mathjax-config">
      MathJax.Hub.Config({
        tex2jax: {inlineMath: [["$","$"]]}
      });

            </script>
            <script src="<?php echo MATHJAX_URI ?>"></script>
            <?php
        }
    }

    if (!function_exists('query2title')) {
        /** transforms an array representing a query into a formatted string */
        function query2title(array $query): string
        {
            $headers = [];
            foreach ($query as $k => $v) {
                if ($k == Q_INNER_AUTHOR) {
                    $k = 'author';
                }

                if ($k == BibEntry::Q_INNER_TYPE) {
                    // we changed from x-bibtex-type to type
                    $k = 'type';
                    // and we remove the regexp modifiers ^ $
                    $v = preg_replace('#[$^]#', '', $v);
                }

                if ($k == Q_KEYS) {
                    $v = json_encode(array_values($v));
                }

                if ($k == Q_RANGE) {
                    foreach ($v as $range) {
                        $range = $range[0] . '-' . $range[1];
                    }

                    $v = implode(',', $v);
                }

                $headers[$k] = InternationalizationUtility::translate(ucwords($k)) . ': ' . ucwords(htmlspecialchars(
                    $v,
                    ENT_NOQUOTES | ENT_XHTML,
                    OUTPUT_ENCODING
                ));
            }

            return implode(' &amp; ', $headers);
        }
    }

    /** returns an HTTP 404 and displays en error message. */
    function nonExistentBibEntryError()
    {
        header('HTTP/1.1 404 Not found'); ?>
        <b>Sorry, this bib entry does not exist.</b>
        <a href="?">Back to bibtexbrowser</a>
        <?php
        exit;
    }

    /** returns the default CSS of bibtexbrowser */
    function bibtexbrowserDefaultCSS()
    {
        ?>

    /* title */
    .bibtitle { font-weight:bold; }
    /* author */
    .bibauthor { /* nothing by default */ }
    /* booktitle (e.g. proceedings title, journal name, etc )*/
    .bibbooktitle { font-style:italic; }
    /* publisher */
    .bibpublisher { /* nothing by default */ }


    /* 1st level headers, equivalent H1 */
    .rheader {
    color: #003366;
    font-size: large;
    font-weight: bold;
    }

    /* 2nd level headers, equivalent H2 */
    .sheader {
    font-weight: bold;
    background-color: #003366;
    color: #ffffff;
    padding: 2px;
    margin-bottom: 0px;
    margin-top: 7px;
    border-bottom: #ff6633 2px solid;

    }

    /* 3rd level headers, equivalent H3 */
    .theader {
    background-color: #995124;
    color: #FFFFFF;
    padding: 1px 2px 1px 2px;
    }

    .btb-nav-title {
    background-color: #995124;
    color: #FFFFFF;
    padding: 1px 2px 1px 2px;
    }

    .menu {
    font-size: x-small;
    background-color: #EFDDB4;
    padding: 0px;
    border: 1px solid #000000;
    margin: 0px;
    }
    .menu a {
    text-decoration: none;
    color: #003366;
    }
    .menu a:hover {
    color: #ff6633;
    }

    dd {
    display: inline; /* for
    <dt> if BIBTEXBROWSER_LAYOUT='definition' */
        }

        .bibitem {
        margin-left:5px;
        }

        .bibref {
        padding:7px;
        padding-left:15px;
        vertical-align:text-top;
        display: inline; /* for
    <dt> if BIBTEXBROWSER_LAYOUT='definition' */
        }

        .result {
        border: 1px solid #000000;
        margin:0px;
        background-color: #ffffff;
        width:100%;
        }
        .result a {
        text-decoration: none;
        color: #469AF8;
        }

        .result a:hover {
        color: #ff6633;
        }

        .input_box{
        margin-bottom : 2px;
        }
        .mini_se {
        border: none 0;
        border-top: 1px dashed #717171;
        height: 1px;
        }
        .a_name a {
        color:#469AF8;
        width:130px;
        }

        .rsslink {
        text-decoration: none;
        color:#F88017;
        /* could be fancy, see : http://www.feedicons.com/ for icons*/
        /*background-image: url("rss.png"); text-indent: -9999px;*/
        }

        .purebibtex {
        font-family: monospace;
        font-size: small;
        border: 1px solid #DDDDDD;
        background: none repeat scroll 0 0 #F5F5F5;
        padding:10px;

        overflow:auto;
        width:600px;

        clear:both;
        }
        .bibentry-by { font-style: italic; }
        .bibentry-abstract { margin:15px; }
        .bibentry-label { margin-top:15px; }
        .bibentry-reference { margin-bottom:15px; padding:10px; background: none repeat scroll 0 0 #F5F5F5; border: 1px
        solid #DDDDDD; }

        .btb-nav { text-align: right; }

        <?php
    }

    /**
     * does nothing but calls method display() on the content.
     * usage:
     * <pre>
     * $db = zetDB('bibacid-utf8.bib');
     * $dis = new SimpleDisplay($db);
     * NoWrapper($dis);
     * </pre>
     */
    function NoWrapper($content)
    {
        echo $content->display();
        if (c('BIBTEXBROWSER_USE_PROGRESSIVE_ENHANCEMENT')) {
            TemplateUtility::javascript();
        }
    }

    function bibtexbrowser_cli($arguments)
    {
        $db = new \BibtexBrowser\BibtexBrowser\BibDataBase();
        $db->load($arguments[1]);

        $current_entry = null;
        $current_field = null;
        $argumentsCount = count($arguments);
        for ($i = 2; $i < $argumentsCount; ++$i) {
            $arg = $arguments[$i];
            if ($arg === '--id') {
                $current_entry = $db->getEntryByKey($arguments[$i + 1]);
                ++$i;
            }

            if (preg_match('#^--set-(.*)#', $arg, $matches)) {
                $current_entry->setField($matches[1], $arguments[$i + 1]);
                ++$i;
            }
        }

        file_put_contents($arguments[1], $db->toBibtex());
    }
}

        @include(preg_replace('#\.php$#', '.after.php', __FILE__));
        $class = BIBTEXBROWSER_MAIN;// extension point
        $main = new $class();
        $main->main();
        ?>
