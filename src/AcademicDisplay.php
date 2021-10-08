<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** displays the publication records sorted by publication types (as configured by constant BIBLIOGRAPHYSECTIONS).
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $d = new AcademicDisplay();
 * $d->setDB($db);
 * $d->display();
 * </pre>
 */
class AcademicDisplay
{
    public string $title = '';

    /**
     * @var mixed
     */
    public $entries;

    public ?BibDataBase $db = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): AcademicDisplay
    {
        $this->title = $title;
        return $this;
    }

    public function setDB($bibdatabase): void
    {
        $this->setEntries($bibdatabase->bibdb);
    }

    /** sets the entries to be shown */
    public function setEntries($entries): void
    {
        $this->entries = $entries;
    }

    /** transforms a query to HTML
     * $ query is an array (e.g. array(Q_YEAR=>'2005'))
     * $title is a string, the title of the section
     */
    public function search2html($query, $title): void
    {
        $entries = $this->db->multisearch($query);
        if (count($entries) > 0) {
            echo "\n" . '<div class="sheader">' . $title . '</div>' . "\n";
        }

        $display = new SimpleDisplay();
        $display->setEntries($entries);

        $display->headerCSS = 'theader';
        $display->display();
    }

    public function display(): void
    {
        $this->db = new BibDataBase();
        $this->db->bibdb = $this->entries;

        if (BIBTEXBROWSER_ACADEMIC_TOC !== true) {
            foreach (_DefaultBibliographySections() as $section) {
                $this->search2html($section['query'], $section['title']);
            }
        } else {
            $sections = [];
            echo '<ul>';

            foreach (_DefaultBibliographySections() as $section) {
                $entries = $this->db->multisearch($section['query']);

                if (count($entries) > 0) {
                    $anchor = preg_replace('#[^a-zA-Z]#', '', $section['title']);
                    echo '<li><a href="#' . $anchor . '">' . $section['title'] . ' (' . count($entries) . ')</a></li>';

                    $display = new SimpleDisplay();
                    $display->incHeadingLevel();
                    $display->setEntries($entries);
                    $display->headerCSS = 'theader';

                    $sections[] = [
                        'display' => $display,
                        'anchor' => $anchor,
                        'title' => $section['title'],
                        'count' => count($entries)
                    ];
                }
            }

            echo '</ul>';

            foreach ($sections as $section) {
                echo "\n<a title=\"" . $section['anchor'] . '"></a>';
                echo '<h' . BIBTEXBROWSER_HTMLHEADINGLEVEL . '>';
                echo $section['title'] . ' (' . $section['count'] . ')';
                echo '</h' . BIBTEXBROWSER_HTMLHEADINGLEVEL . ">\n",
                $section['display']->display();
            }
        }
    }
}
