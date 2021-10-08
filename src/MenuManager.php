<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

use BibtexBrowser\BibtexBrowser\Utility\InternationalizationUtility;

/** is used for creating menus (by type, by year, by author, etc.).
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $menu = new MenuManager();
 * $menu->setDB($db);
 * $menu->year_size=100;// should display all years :)
 * $menu->display();
 * </pre>
 */
class MenuManager
{
    public $display;

    /** The bibliographic database, an instance of class BibDataBase. */
    public $db;

    public $type_size = TYPES_SIZE;

    public $year_size = YEAR_SIZE;

    public $author_size = AUTHORS_SIZE;

    public $tag_size = TAGS_SIZE;

    public function __construct()
    {
    }

    /** sets the database that is used to create the menu */
    public function setDB($db): MenuManager
    {
        $this->db = $db;
        return $this;
    }

    public function getTitle()
    {
        return '';
    }

    public function metadata(): array
    {
        return [['robots', 'noindex']];
    }

    /** function called back by HTMLTemplate */
    public function display(): void
    {
        echo $this->searchView() . '<br/>';
        echo $this->typeVC() . '<br/>';
        echo $this->yearVC() . '<br/>';
        echo $this->authorVC() . '<br/>';
        echo $this->tagVC() . '<br/>';
    }

    /** Displays the title in a table. */
    public function titleView()
    {
        ?>
        <table>
            <tr>
                <td class="rheader">Generated from <?php echo $_GET[Q_FILE]; ?></td>
            </tr>
        </table>
        <?php
    }

    /** Displays the search view in a form. */
    public function searchView()
    {
        ?>
        <form action="?" method="get" target="<?php echo BIBTEXBROWSER_MENU_TARGET; ?>">
            <input type="text" name="<?php echo Q_SEARCH; ?>" class="input_box" size="18"/>
            <input type="hidden" name="<?php echo Q_FILE; ?>" value="<?php echo @$_GET[Q_FILE]; ?>"/>
            <br/>
            <input type="submit" value="search" class="input_box"/>
        </form>
        <?php
    }

    /** Displays and controls the types menu in a table. */
    public function typeVC()
    {
        $types = [];
        foreach ($this->db->getTypes() as $type) {
            $types[$type] = $type;
        }

        $types['.*'] = 'all types';
        // retreive or calculate page number to display
        $page = isset($_GET[Q_TYPE_PAGE]) ? (int) $_GET[Q_TYPE_PAGE] : 1;

        $this->displayMenu('Types', $types, $page, $this->type_size, Q_TYPE_PAGE, BibEntry::Q_INNER_TYPE);
    }

    /** Displays and controls the authors menu in a table. */
    public function authorVC()
    {
        // retrieve authors list to display
        $authors = $this->db->authorIndex();

        // determine the authors page to display
        $page = isset($_GET[Q_AUTHOR_PAGE]) ? (int) $_GET[Q_AUTHOR_PAGE] : 1;


        $this->displayMenu(
            'Authors',
            $authors,
            $page,
            $this->author_size,
            Q_AUTHOR_PAGE,
            Q_AUTHOR
        );
    }

    /** Displays and controls the tag menu in a table. */
    public function tagVC()
    {
        // retrieve authors list to display
        $tags = $this->db->tagIndex();

        // determine the authors page to display
        $page = isset($_GET[Q_TAG_PAGE]) ? (int) $_GET[Q_TAG_PAGE] : 1;

        if (count($tags) > 0) {
            $this->displayMenu(
                'Keywords',
                $tags,
                $page,
                $this->tag_size,
                Q_TAG_PAGE,
                Q_TAG
            );
        }
    }

    /** Displays and controls the tag menu in a table. */
    public function yearVC(): void
    {
        // retrieve authors list to display
        $years = $this->db->yearIndex();

        // determine the authors page to display
        $page = isset($_GET[Q_YEAR_PAGE]) ? (int) $_GET[Q_YEAR_PAGE] : 1;


        $this->displayMenu(
            'Years',
            $years,
            $page,
            $this->year_size,
            Q_YEAR_PAGE,
            Q_YEAR
        );
    }

    /** Displays the main contents . */
    public function mainVC(): void
    {
        $this->display->display();
    }

    /** Displays a list menu in a table.
     *
     * $title: title of the menu (string)
     * $list: list of menu items (string)
     * $page: page number to display (number)
     * $pageSize: size of each page
     * $pageKey: URL query name to send the page number to the server
     * $targetKey: URL query name to send the target of the menu item
     */
    public function displayMenu(
        string $title,
        array $list,
        int $page,
        int $pageSize,
        string $pageKey,
        string $targetKey
    ): void {
        $numEntries = count($list);
        $startIndex = ($page - 1) * $pageSize;
        $endIndex = $startIndex + $pageSize; ?>
        <table style="width:100%" class="menu">
            <tr>
                <td>
                    <!-- this table is used to have the label on the left
                    and the navigation links on the right -->
                    <table style="width:100%" border="0" cellspacing="0" cellpadding="0">
                        <tr class="btb-nav-title">
                            <td><b><?php echo $title; ?></b></td>
                            <td class="btb-nav"><b>
                                    <?php echo $this->menuPageBar(
            $pageKey,
            $numEntries,
            $page,
            $pageSize,
            $startIndex,
            $endIndex
        ); ?></b></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="btb-menu-items">
                    <?php $this->displayMenuItems(
            $list,
            $startIndex,
            $endIndex,
            $targetKey
        ); ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /** Returns a string to displays forward and reverse page controls.
     *
     * $queryKey: key to send the page number as a URL query string
     * $page: current page number to display
     * $numEntries: number of menu items
     * $start: start index of the current page
     * $end: end index of the current page
     */
    public function menuPageBar(
        $queryKey,
        int $numEntries,
        int $page,
        $pageSize,
        $start,
        $end
    ): string {
        $result = '';

        // (1 page) reverse (<)
        if ($start > 0) {
            $href = makeHref([$queryKey => $page - 1, 'menu' => '']);//menuPageBar
            $result .= '<a ' . $href . "><b>[prev]</b></a>\n";
        }

        // (1 page) forward (>)
        if ($end < $numEntries) {
            $href = makeHref([$queryKey => $page + 1, 'menu' => '']);//menuPageBar
            $result .= '<a ' . $href . "><b>[next]</b></a>\n";
        }

        return $result;
    }

    /**
     * Displays menu items (anchors) from the start index (inclusive) to
     * the end index (exclusive). For each menu, the following form of
     * string is printed:
     *
     * <a href="...?bib=cheon.bib&author=Yoonsik+Cheon">
     *    Cheon, Yoonsik</a>
     * <div class="mini_se"></div>
     */
    public function displayMenuItems($items, $startIndex, $endIndex, $queryKey): void
    {
        $index = 0;
        foreach ($items as $key => $item) {
            if ($index >= $startIndex && $index < $endIndex) {
                $href = $queryKey === 'year' ? makeHref([$queryKey => InternationalizationUtility::translate($item)]) : makeHref([$queryKey => $key]);
                echo '<a ' . $href . ' target="' . BIBTEXBROWSER_MENU_TARGET . '">' . $item . "</a>\n";
                echo "<div class=\"mini_se\"></div>\n";
            }

            ++$index;
        }
    }
}
