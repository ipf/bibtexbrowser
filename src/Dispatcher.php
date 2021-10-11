<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

use BibtexBrowser\BibtexBrowser\Configuration\Configuration;
use BibtexBrowser\BibtexBrowser\Display\BibEntryDisplay;
use BibtexBrowser\BibtexBrowser\Display\BibtexDisplay;
use BibtexBrowser\BibtexBrowser\Display\NotFoundDisplay;
use BibtexBrowser\BibtexBrowser\Display\RSSDisplay;

/** is responsible for transforming a query string of $_GET[..] into a publication list.
 * usage:
 * <pre>
 * $_GET['library']=1;
 * @require('bibtexbrowser.php');
 * $_GET['bib']='bibacid-utf8.bib';
 * $_GET['year']='2006';
 * $x = new Dispatcher();
 * $x->main();
 * </pre>
 */
class Dispatcher
{

    /** this is the query */
    public array $query = [];

    /**
     * the displayer of selected entries. The default is set in BIBTEXBROWSER_DEFAULT_DISPLAY.
     *  It could also be an RSSDisplay if the rss keyword is present
     */
    public $displayer = '';

    /**
     * the wrapper of selected entries. The default is an HTML wrapper
     *  It could also be a NoWrapper when you include your pub list in your home page
     */
    public $wrapper = Configuration::BIBTEXBROWSER_DEFAULT_TEMPLATE;

    /** The BibDataBase object */
    public $db = null;

    public function __construct()
    {
    }

    /** returns the underlying BibDataBase object */
    public function getDB()
    {
        // by default set it from $_GET[Q_FILE]
        // first we set the database (load from disk or parse the bibtex file)
        if ($this->db == null) {
            list($db, $parsed, $updated, $saved) = _zetDB($_GET[Configuration::Q_FILE]);
            $this->db = $db;
        }

        return $this->db;
    }

    public function main()
    {
        // are we in test mode, or libray mode
        // then this file is just a library
        if (isset($_GET['test']) || isset($_GET['library'])) {
            // we unset in  order to use the dispatcher afterwards
            unset($_GET['test']);
            unset($_GET['library']);
            return;
        }

        if (!isset($_GET[Configuration::Q_FILE])) {
            die('$_GET[\'' . Configuration::Q_FILE . "'] is not set!");
        }

        // is the publication list included in another page?
        // strtr is used for Windows where __FILE__ contains C:\toto and SCRIPT_FILENAME contains C:/toto (bug reported by Marco)
        // realpath is required if the path contains sym-linked directories (bug found by Mark Hereld)
        if (strtr(realpath(__FILE__), '\\', '/') !== strtr(realpath($_SERVER['SCRIPT_FILENAME']), '\\', '/')) {
            $this->wrapper = BIBTEXBROWSER_EMBEDDED_WRAPPER;
        }

        // first pass, we will exit if we encounter key or menu or academic
        // other wise we just create the $this->query
        foreach (array_keys($_GET) as $keyword) {
            // if the return value is END_DISPATCH, we finish bibtexbrowser (but not the whole PHP process in case we are embedded)
            if (method_exists($this, $keyword) && $this->$keyword() === 'END_DISPATCH') {
                return;
            }
        }

        // at this point, we may have a query

        if ($this->query !== []) {

            // first test for inconsistent queries
            if (isset($this->query[Configuration::Q_ALL]) && count($this->query) > 1) {
                // we discard the Q_ALL, it helps in embedded mode
                unset($this->query[Configuration::Q_ALL]);
            }

            $selectedEntries = $this->getDB()->multisearch($this->query);

            if (count($selectedEntries) == 0) {
                $this->displayer = NotFoundDisplay::class;
            }

            // default order
            uasort($selectedEntries, 'compare_bib_entries');
            $selectedEntries = array_values($selectedEntries);

            if ($this->displayer == '') {
                $this->displayer = Configuration::BIBTEXBROWSER_DEFAULT_DISPLAY;
            }
        }

        // otherwise the query is left empty

        // do we have a displayer?
        if ($this->displayer != '') {
            $options = [];
            if (isset($_GET['dopt'])) {
                $options = json_decode($_GET['dopt'], true);
            }

            // required for PHP4 to have this intermediate variable
            $x = new $this->displayer();
            if (method_exists($x, 'setEntries')) {
                $x->setEntries($selectedEntries);
            }

            if (method_exists($x, 'setTitle')) {
                $x->setTitle(query2title($this->query));
            }

            if (method_exists($x, 'setQuery')) {
                $x->setQuery($this->query);
            }

            // should call method display() on $x
            $fun = $this->wrapper;
            $fun($x);
            $this->clearQuery();
        } elseif (headers_sent() == false) {
            /* to avoid sending an unnecessary frameset */
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?frameset&bib=' . $_GET[Configuration::Q_FILE]);
        }
    }

    /** clears the query string in $_GET so that bibtexbrowser can be called multiple times */
    public function clearQuery()
    {
        $params = [
            Configuration::Q_ALL,
            'rss',
            'astext',
            Configuration::Q_SEARCH,
            Configuration::Q_EXCLUDE,
            Configuration::Q_YEAR,
            Configuration::EDITOR,
            Configuration::Q_TAG,
            Configuration::Q_AUTHOR,
            Configuration::Q_TYPE,
            Configuration::Q_ACADEMIC,
            Configuration::Q_KEY
        ];
        foreach ($params as $p) {
            unset($_GET[$p]);
        }
    }

    public function all()
    {
        $this->query[Configuration::Q_ALL] = 1;
    }

    public function display()
    {
        $this->displayer = $_GET['display'];
    }

    public function rss(): void
    {
        $this->displayer = RSSDisplay::class;
        $this->wrapper = 'NoWrapper';
    }

    public function astext(): void
    {
        $this->displayer = BibtexDisplay::class;
        $this->wrapper = 'NoWrapper';
    }

    public function search()
    {
        if (preg_match('#utf-?8#i', Configuration::OUTPUT_ENCODING)) {
            $_GET[Configuration::Q_SEARCH] = urldecode($_GET[Configuration::Q_SEARCH]);
        }

        $this->query[Configuration::Q_SEARCH] = $_GET[Configuration::Q_SEARCH];
    }

    public function exclude()
    {
        $this->query[Configuration::Q_EXCLUDE] = $_GET[Configuration::Q_EXCLUDE];
    }

    public function year()
    {
        // we may want the latest
        if ($_GET[Configuration::Q_YEAR] === 'latest') {
            $years = $this->getDB()->yearIndex();
            $_GET[Configuration::Q_YEAR] = array_shift($years);
        }

        $this->query[Configuration::Q_YEAR] = $_GET[Configuration::Q_YEAR];
    }

    public function editor()
    {
        $this->query[Configuration::EDITOR] = $_GET[Configuration::EDITOR];
    }

    public function keywords(): void
    {
        $this->query[Configuration::Q_TAG] = $_GET[Configuration::Q_TAG];
    }

    public function author(): void
    {
        // Friday, October 29 2010
        // changed from 'author' to '_author'
        // in order to search at the same time "Joe Dupont" an "Dupont, Joe"
        $this->query[Q_INNER_AUTHOR] = $_GET[Configuration::Q_AUTHOR];
    }

    public function type(): void
    {
        $this->query[Configuration::Q_TYPE] = $_GET[Configuration::Q_TYPE];
    }

    /**
     * Allow the user to search for a range of dates
     *
     * The query string can comprise several elements separated by commas and
     * optionally white-space.
     * Each element can either be one number (a year) or two numbers
     * (a range of years) separated by anything non-numerical.
     *
     */
    public function range(): void
    {
        $ranges = explode(',', $_GET[Configuration::Q_RANGE]);
        $result = [];

        $nextYear = 1 + (int)date('Y');
        $nextYear2D = $nextYear % 100;
        $thisCentury = $nextYear - $nextYear2D;

        foreach ($ranges as $range) {
            $range = trim($range);
            preg_match('#(\d*)([^0-9]*)(\d*)#', $range, $matches);
            array_shift($matches);

            // If the number is empty, leave it empty - dont put it to 0
            // If the number is two-digit, assume it to be within the last century or next year
            if ($matches[0] === '') {
                $lower = '';
            } elseif ($matches[0] < 100) {
                $lower = $matches[0] > $nextYear2D ? $thisCentury + $matches[0] - 100 : $thisCentury + $matches[0];
            } else {
                $lower = $matches[0];
            }

            // If no separator to indicate a range of years was supplied,
            // the upper and lower boundaries are the same.
            //
            // Otherwise, again:
            // If the number is empty, leave it empty - dont put it to 0
            // If the number is two-digit, assume it to be within the last century or next year
            if ($matches[1] === '') {
                $upper = $lower;
            } elseif ($matches[2] === '') {
                $upper = '';
            } elseif ($matches[2] < 100) {
                $upper = $matches[2] > $nextYear2D ? $thisCentury + $matches[2] - 100 : $thisCentury + $matches[2];
            } else {
                $upper = $matches[2];
            }

            $result[] = [$lower, $upper];
        }

        $this->query[Configuration::Q_RANGE] = $result;
    }

    public function menu(): string
    {
        $menu = new MenuManager();
        $menu->setDB($this->getDB());

        $fun = $this->wrapper;
        $fun($menu);
        return 'END_DISPATCH';
    }

    /** the academic keyword in URLs switch from a year based viey to a publication type based view */
    public function academic(): void
    {
        $this->displayer = 'AcademicDisplay';


        // backward compatibility with old GET API
        // this is deprecated
        // instead of academic=Martin+Monperrus
        // you should use author=Martin+Monperrus&academic
        // be careful of the semantics of === and !==
        // 'foo bar' == true is true
        // 123 == true is true (and whatever number different from 0
        // 0 == true is true
        // '1'!=1 is **false**
        if (!isset($_GET[Configuration::Q_AUTHOR]) && $_GET[Configuration::Q_ACADEMIC] !== true && $_GET[Configuration::Q_ACADEMIC] !== 'true' && $_GET[Configuration::Q_ACADEMIC] != 1 && $_GET[Configuration::Q_ACADEMIC] != '') {
            $_GET[Configuration::Q_AUTHOR] = $_GET[Configuration::Q_ACADEMIC];
            $this->query[Configuration::Q_AUTHOR] = $_GET[Configuration::Q_ACADEMIC];
        }
    }

    public function key()
    {
        $entries = [];
        // case 1: this is a single key
        if ($this->getDB()->contains($_GET[Configuration::Q_KEY])) {
            $entries[] = $this->getDB()->getEntryByKey($_GET[Configuration::Q_KEY]);
            if (isset($_GET['astext'])) {
                $bibdisplay = new BibtexDisplay();
                $bibdisplay->setEntries($entries);
                $bibdisplay->display();
            } else {
                $bibdisplay = new BibEntryDisplay();
                $bibdisplay->setEntries($entries);
                $fun = $this->wrapper;
                $fun($bibdisplay);
            }

            return 'END_DISPATCH';
        }

        // case two: multiple keys
        if (preg_match('#[|,]#', $_GET[Configuration::Q_KEY])) {
            $this->query[Configuration::Q_SEARCH] = str_replace(',', '|', $_GET[Configuration::Q_KEY]);
        } else {
            nonExistentBibEntryError();
        }
    }

    public function keys()
    {
        $_GET[Configuration::Q_KEYS] = (array)json_decode(urldecode($_GET[Configuration::Q_KEYS])); // decode and cast the object into an (associative) array
        // Make the array 1-based (keeps the string keys unchanged)
        array_unshift($_GET[Configuration::Q_KEYS], '__DUMMY__');
        unset($_GET[Configuration::Q_KEYS][0]);
        // Keep a flipped version for efficient search in getRawAbbrv()
        $_GET[Q_INNER_KEYS_INDEX] = array_flip($_GET[Configuration::Q_KEYS]);
        $this->query[Configuration::Q_KEYS] = $_GET[Configuration::Q_KEYS];
    }

    /** is used to remotely analyzed a situation */
    public function diagnosis()
    {
        header('Content-type: text/plain');
        echo 'php version: ' . phpversion() . "\n";
        echo "bibtexbrowser version: __GITHUB__\n";
        echo 'dir: ' . decoct(fileperms(__DIR__)) . "\n";
        echo 'bibtex file: ' . decoct(fileperms($_GET[Configuration::Q_FILE])) . "\n";
        exit;
    }

    public function frameset()
    { ?>


        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta name="generator" content="bibtexbrowser v__GITHUB__"/>
            <meta http-equiv="Content-Type" content="text/html; charset=<?php echo Configuration::OUTPUT_ENCODING ?>"/>
            <title>You are browsing <?php echo htmlentities($_GET[Configuration::Q_FILE], ENT_QUOTES); ?> with bibtexbrowser</title>
        </head>
        <frameset cols="15%,*">
            <frame name="menu" src="<?php echo '?' . Configuration::Q_FILE . '=' . urlencode($_GET[Configuration::Q_FILE]) . '&amp;menu'; ?>"/>
            <frame name="main"
                   src="<?php echo '?' . Configuration::Q_FILE . '=' . urlencode($_GET[Configuration::Q_FILE]) . '&amp;' . BIBTEXBROWSER_DEFAULT_FRAME ?>"/>
        </frameset>
        </html>

        <?php
        return 'END_DISPATCH';
    }
}
