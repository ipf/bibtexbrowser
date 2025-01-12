<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

use BibtexBrowser\BibtexBrowser\Configuration\Configuration;

/** outputs an horizontal  year-based menu
 * usage:
 * <pre>
 * $_GET['library']=1;
 * $_GET['bib']='bibacid-utf8.bib';
 * $_GET['all']=1;
 * include( 'bibtexbrowser.php' );
 * setDB();
 * new IndependentYearMenu($_GET[Q_DB]);
 * </pre>
 */
class IndependentYearMenu
{
    public function __construct($db)
    {
        $yearIndex = $db->yearIndex();
        echo '<div id="yearmenu">Year: ';
        $formatedYearIndex = [];
        $formatedYearIndex[] = '<a ' . makeHref([Configuration::Q_YEAR => '.*']) . '>All</a>';
        foreach ($yearIndex as $year) {
            $formatedYearIndex[] = '<a ' . makeHref([Configuration::Q_YEAR => $year]) . '>' . $year . '</a>';
        }

        // by default the separator is a |
        echo implode('|', $formatedYearIndex);
        echo '</div>';
    }
}
