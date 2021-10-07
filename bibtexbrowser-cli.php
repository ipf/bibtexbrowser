<?php
require(__DIR__ . '/vendor/autoload.php');
// a command line interface for bibtexbrowser
// example: php bibtexbrowser-cli.php test_cli.bib --id classical --set-title "a new title"
$_GET['library']=1;
function createBibEntry()
{
    return new \BibtexBrowser\BibtexBrowser\RawBibEntry();
}
require(__DIR__ . '/bibtexbrowser.php');
bibtexbrowser_configure('BIBTEXBROWSER_BIBTEX_VIEW', 'reconstructed');
bibtexbrowser_configure('BIBTEXBROWSER_BIBTEX_VIEW_FILTEREDOUT', '');
bibtexbrowser_cli($argv);
