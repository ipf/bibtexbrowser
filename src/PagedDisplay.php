<?php

namespace BibtexBrowser\BibtexBrowser;

/** creates paged output, e.g: [[http://localhost/bibtexbrowser/testPagedDisplay.php?page=1]]
 * usage:
 * <pre>
 * $_GET['library']=1;
 * include( 'bibtexbrowser.php' );
 * $db = zetDB('bibacid-utf8.bib');
 * $pd = new PagedDisplay();
 * $pd->setEntries($db->bibdb);
 * $pd->display();
 * </pre>
 */
class PagedDisplay
{
    public array $entries;
    /**
     * @var int|mixed
     */
    public $page;
    public array $query = [];

    public function __construct()
    {
        $this->setPage();
    }

    /** sets the entries to be shown */
    public function setEntries($entries)
    {
        uasort($entries, 'compare_bib_entries');
        $this->entries = array_values($entries);
    }

    /** sets $this->page from $_GET, defaults to 1 */
    public function setPage()
    {
        $this->page = 1;
        if (isset($_GET['page'])) {
            $this->page = $_GET['page'];
        }
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function getTitle()
    {
        return query2title($this->query) . ' - page ' . $this->page;
    }

    public function display()
    {
        $less = false;

        if ($this->page > 1) {
            $less = true;
        }

        $more = true;

        // computing $more
        $index = ($this->page) * bibtexbrowser_configuration('PAGE_SIZE');
        if (!isset($this->entries[$index])) {
            $more = false;
        }

        $this->menu($less, $more);
        print_header_layout();
        for ($i = 0; $i < bibtexbrowser_configuration('PAGE_SIZE'); $i++) {
            $index = ($this->page - 1) * bibtexbrowser_configuration('PAGE_SIZE') + $i;
            if (isset($this->entries[$index])) {
                $bib = $this->entries[$index];
                echo $bib->toHTML(true);
            } else {
                //break;
            }
        } // end foreach

        print_footer_layout();

        $this->menu($less, $more);
    }

    public function menu($less, $more)
    {
        echo '<span class="nav-menu">';

        $prev = $this->query;
        $prev['page'] = $this->page - 1;
        if ($less == true) {
            echo '<a ' . makeHref($prev) . '>Prev Page</a>';
        }

        if ($less && $more) {
            echo '&nbsp;|&nbsp;';
        }

        $next = $this->query;
        $next['page'] = $this->page + 1;
        if ($more == true) {
            echo '<a ' . makeHref($next) . '>Next Page</a>';
        }
        echo '</span>';
    }
}
