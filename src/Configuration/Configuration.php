<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser\Configuration;

use BibtexBrowser\BibtexBrowser\Display\SimpleDisplay;
use BibtexBrowser\BibtexBrowser\Utility\TemplateUtility;
use Exception;
use MyCLabs\Enum\Enum;

class Configuration extends Enum
{
    // the encoding of your bibtex file
    /**
     * @var string
     */
    public const BIBTEX_INPUT_ENCODING = 'UTF-8';

    // the encoding of the HTML output
    /**
     * @var string
     */
    public const OUTPUT_ENCODING = 'UTF-8';

    // number of bib items per page
    // we use the same parameter 'num' as Google
    /**
     * @var int
     */
    public const PAGE_SIZE = 14;

    // bibtexbrowser uses a small piece of Javascript to improve the user experience
    // see http://en.wikipedia.org/wiki/Progressive_enhancement
    // if you don't like it, you can be disable it by adding in bibtexbrowser.local.php
    // @define('BIBTEXBROWSER_USE_PROGRESSIVE_ENHANCEMENT',false);
    /**
     * @var bool
     */
    public const BIBTEXBROWSER_USE_PROGRESSIVE_ENHANCEMENT = true;

    // this is the name of a function
    /**
     * @var string
     */
    public const BIBLIOGRAPHYSTYLE = 'DefaultBibliographyStyle';

    // this is the name of a function
    /**
     * @var string
     */
    public const BIBLIOGRAPHYSECTIONS = 'DefaultBibliographySections';

    // this is the name of a function
    /**
     * @var string
     */
    public const BIBLIOGRAPHYTITLE = 'DefaultBibliographyTitle';

    // shall we load MathJax to render math in $…$ in HTML?
    /**
     * @var bool
     */
    public const BIBTEXBROWSER_RENDER_MATH = true;

    /**
     * @var string
     */
    public const MATHJAX_URI = '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/config/TeX-AMS_HTML.js?V=2.7.1';

    // the default jquery URI
    /**
     * @var string
     */
    public const JQUERY_URI = '//code.jquery.com/jquery-1.5.1.min.js';

    // can we load bibtex files on external servers?
    /**
     * @var bool
     */
    public const BIBTEXBROWSER_LOCAL_BIB_ONLY = true;

    // the default view in {SimpleDisplay,AcademicDisplay,RSSDisplay,BibtexDisplay}
    /**
     * @var string
     */
    public const BIBTEXBROWSER_DEFAULT_DISPLAY = SimpleDisplay::class;

    // the default template
    /**
     * @var string
     */
    public const BIBTEXBROWSER_DEFAULT_TEMPLATE = TemplateUtility::class;

    // the target frame of menu links
    /**
     * @var string
     */
    public const BIBTEXBROWSER_MENU_TARGET = 'main';

    // may be year/x-abbrv/key/none/index/keys-index
    /**
     * @var string
     */
    public const ABBRV_TYPE = 'index';

    // are robots allowed to crawl and index bibtexbrowser generated pages?
    /**
     * @var false
     */
    public const BIBTEXBROWSER_ROBOTS_NOINDEX = false;

    //the default view in the "main" (right hand side) frame
    // year=latest,all and all valid bibtexbrowser queries
    /**
     * @var string
     */
    public const BIBTEXBROWSER_DEFAULT_FRAME = 'year=latest';

    // Wrapper to use when we are included by another script
    /**
     * @var string
     */
    public const BIBTEXBROWSER_EMBEDDED_WRAPPER = 'NoWrapper';

    // Main class to use
    /**
     * @var string
     */
    public const BIBTEXBROWSER_MAIN = \BibtexBrowser\BibtexBrowser\Dispatcher::class;

    // default order functions
    // Contract Returns < 0 if str1 is less than str2; > 0 if str1 is greater than str2, and 0 if they are equal.
    // can be @define('ORDER_FUNCTION','compare_bib_entry_by_title');
    // can be @define('ORDER_FUNCTION','compare_bib_entry_by_bibtex_order');
    /**
     * @var string
     */
    public const ORDER_FUNCTION = 'compare_bib_entry_by_year';

    /**
     * @var string
     */
    public const ORDER_FUNCTION_FINE = 'compare_bib_entry_by_month';

    // only displaying the n newest entries
    /**
     * @var int
     */
    public const BIBTEXBROWSER_NEWEST = 5;

    /**
     * @var false
     */
    public const BIBTEXBROWSER_NO_DEFAULT = false;

    // BIBTEXBROWSER_LINK_STYLE defines which function to use to display the links of a bibtex entry
    // can be 'nothing' (a function that does nothing)
    /**
     * @var string
     */
    public const BIBTEXBROWSER_LINK_STYLE = 'bib2links_default';

    // do we add [bibtex] links ?
    /**
     * @var bool
     */
    public const BIBTEXBROWSER_BIBTEX_LINKS = true;

    // do we add [pdf] links ?
    // if the file extention is not .pdf, the field name (pdf, url, or file) is used instead
    /**
     * @var bool
     */
    public const BIBTEXBROWSER_PDF_LINKS = true;

    // do we add [doi] links ?
    /**
     * @var bool
     */
    public const BIBTEXBROWSER_DOI_LINKS = true;

    // do we add [gsid] links (Google Scholar)?
    /**
     * @var bool
     */
    public const BIBTEXBROWSER_GSID_LINKS = true;

    // should pdf, doi, url, gsid links be opened in a new window?
    // can be _blank (new window), _top (with frames)
    /**
     * @var string
     */
    public const BIBTEXBROWSER_LINKS_TARGET = '_self';

    // should authors be linked to [none/homepage/resultpage]
    // none: nothing
    // their homepage if defined as @strings
    // their publication lists according to this bibtex
    /**
     * @var string
     */
    public const BIBTEXBROWSER_AUTHOR_LINKS = 'homepage';

    // BIBTEXBROWSER_LAYOUT defines the HTML rendering layout of the produced HTML
    // may be table/list/ordered_list/definition/none (for <table>, <ol>, <dl>, nothing resp.).
    // for list/ordered_list, the abbrevations are not taken into account (see ABBRV_TYPE)
    // for ordered_list, the index is given by HTML directly (in increasing order)
    /**
     * @var string
     */
    public const BIBTEXBROWSER_LAYOUT = 'table';

    // should the original bibtex be displayed or a reconstructed one with filtering
    // values: original/reconstructed
    // warning, with reconstructed, the latex markup for accents/diacritics is lost
    /**
     * @var string
     */
    public const BIBTEXBROWSER_BIBTEX_VIEW = 'original';

    // a list of fields that will not be shown in the bibtex view if BIBTEXBROWSER_BIBTEX_VIEW=reconstructed
    /**
     * @var string
     */
    public const BIBTEXBROWSER_BIBTEX_VIEW_FILTEREDOUT = 'comment|note|file';

    // should Latex macros be executed (e.g. \'e -> é
    /**
     * @var bool
     */
    public const BIBTEXBROWSER_USE_LATEX2HTML = true;

    // Which is the first html <hN> level that should be used in embedded mode?
    /**
     * @var int
     */
    public const BIBTEXBROWSER_HTMLHEADINGLEVEL = 2;

    /**
     * @var false
     */
    public const BIBTEXBROWSER_ACADEMIC_TOC = false;

    /**
     * @var false
     */
    public const BIBTEXBROWSER_DEBUG = false;

    // how to print authors names?
    // default => as in the bibtex file
    // USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT = true => "Meyer, Herbert"
    // USE_INITIALS_FOR_NAMES = true => "Meyer H"
    // USE_FIRST_THEN_LAST => Herbert Meyer
    // output authors in a comma separated form, e.g. "Meyer, H"?
    /**
     * @var false
     */
    public const USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT = false;

    // use only initials for all first names?
    /**
     * @var false
     */
    public const USE_INITIALS_FOR_NAMES = false;

    // put first names before last names?
    /**
     * @var false
     */
    public const USE_FIRST_THEN_LAST = false;

    // if non-empty, use this to separate multiple names regardless of USE_COMMA_AS_NAME_SEPARATOR_IN_OUTPUT
    /**
     * @var string
     */
    public const FORCE_NAMELIST_SEPARATOR = '';

    /**
     * @var string
     */
    public const LAST_AUTHOR_SEPARATOR = ' and ';

    // adds an additional separator in addition to LAST_AUTHOR_SEPARATOR if there are more than two authors
    /**
     * @var false
     */
    public const USE_OXFORD_COMMA = false;

    // number of entry types per table
    /**
     * @var int
     */
    public const TYPES_SIZE = 10;

    // number of years per table
    /**
     * @var int
     */
    public const YEAR_SIZE = 20;

    // number of authors per table
    /**
     * @var int
     */
    public const AUTHORS_SIZE = 30;

    // number of keywords per table
    /**
     * @var int
     */
    public const TAGS_SIZE = 30;

    /**
     * @var int
     */
    public const READLINE_LIMIT = 1024;

    /**
     * @var string
     */
    public const Q_YEAR = 'year';

    /**
     * @var string
     */
    public const Q_YEAR_PAGE = 'year_page';

    /**
     * @var string
     */
    public const Q_YEAR_INPRESS = 'in press';

    /**
     * @var string
     */
    public const Q_YEAR_ACCEPTED = 'accepted';

    /**
     * @var string
     */
    public const Q_YEAR_SUBMITTED = 'submitted';

    /**
     * @var string
     */
    public const Q_FILE = 'bib';

    /**
     * @var string
     */
    public const Q_AUTHOR = 'author';

    /**
     * @var string
     */
    public const Q_AUTHOR_PAGE = 'author_page';

    /**
     * @var string
     */
    public const Q_TAG = 'keywords';

    /**
     * @var string
     */
    public const Q_TAG_PAGE = 'keywords_page';

    /**
     * @var string
     */
    public const Q_TYPE = 'type';
    // used for queries
    /**
     * @var string
     */
    public const Q_TYPE_PAGE = 'type_page';

    /**
     * @var string
     */
    public const Q_ALL = 'all';

    /**
     * @var string
     */
    public const Q_ENTRY = 'entry';

    /**
     * @var string
     */
    public const Q_KEY = 'key';

    /**
     * @var string
     */
    public const Q_KEYS = 'keys';
    // filter entries using a url-encoded, JSON-encoded array of bibtex keys
    /**
     * @var string
     */
    public const Q_SEARCH = 'search';

    /**
     * @var string
     */
    public const Q_EXCLUDE = 'exclude';

    /**
     * @var string
     */
    public const Q_RESULT = 'result';

    /**
     * @var string
     */
    public const Q_ACADEMIC = 'academic';

    /**
     * @var string
     */
    public const Q_DB = 'bibdb';

    /**
     * @var string
     */
    public const Q_LATEST = 'latest';

    /**
     * @var string
     */
    public const Q_RANGE = 'range';

    /**
     * @var string
     */
    public const AUTHOR = 'author';

    /**
     * @var string
     */
    public const EDITOR = 'editor';

    /**
     * @var string
     */
    public const SCHOOL = 'school';

    /**
     * @var string
     */
    public const TITLE = 'title';

    /**
     * @var string
     */
    public const BOOKTITLE = 'booktitle';

    /**
     * @var string
     */
    public const YEAR = 'year';

    /**
     * @var string
     */
    public const MULTIPLE_BIB_SEPARATOR = ';';

    // see https://en.wikipedia.org/wiki/COinS
    /**
     * @var bool
     */
    public const METADATA_COINS = true;

    // metadata google scholar, see https://www.monperrus.net/martin/accurate+bibliographic+metadata+and+google+scholar
    /**
     * @var false
     */
    public const METADATA_GS = false;

    // see https://dublincore.org/
    /**
     * @var bool
     */
    public const METADATA_DC = true;

    // see https://ogp.me/
    /**
     * @var bool
     */
    public const METADATA_OPENGRAPH = true;

    // see https://wiki.eprints.org/w/Category:EPrints_Metadata_Fields
    /**
     * @var false
     */
    public const METADATA_EPRINTS = false;

    // define sort order for special values in 'year' field
    // highest number is sorted first
    // don't exceed 0 though, since the values are added to PHP_INT_MAX
    /**
     * @var int
     */
    public const ORDER_YEAR_INPRESS = -0;

    /**
     * @var int
     */
    public const ORDER_YEAR_ACCEPTED = -1;

    /**
     * @var int
     */
    public const ORDER_YEAR_SUBMITTED = -2;

    /**
     * @var int
     */
    public const ORDER_YEAR_OTHERNONINT = -3;


    // in embedded mode, we still need a URL for displaying bibtex entries alone
    // this is usually resolved to bibtexbrowser.php
    // but can be overridden in bibtexbrowser.local.php
    // for instance with @define('BIBTEXBROWSER_URL',''); // links to the current page with ?
    /**
     * @var string
     */
    public const BIBTEXBROWSER_URL = 'basename(__FILE__)';

    // Specify the location of the cache file for servers that need temporary files written in a specific location
    /**
     * @var string
     */
    public const CACHE_DIR = '';

    // Specify the location of the bib file for servers that need do not allow slashes in URLs,
    // where the bib file and bibtexbrowser.php are in different directories.
    /**
     * @var string
     */
    public const DATA_DIR = '';

    public static function config_value($key)
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
     * @throws Exception
     */
    public static function bibtexbrowser_configuration($key)
    {
        if (self::search($key)) {
            return self::search($key);
        }

        throw new Exception('no such configuration parameter: ' . $key);
    }

    /**
     * @throws Exception
     */
    public static function c($key)
    {
        return self::search($key);
    }
}
