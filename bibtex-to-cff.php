<?php
// create CITATION.cff file for Github from a bibtex file
// reference documentation: https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-citation-files#about-citation-files
// script part of https://github.com/monperrus/bibtexbrowser/
//
// Usage
// $ php bibtex-to-cff.php test_cli.bib --id classical
//     -> this creates a file CITATION.cff for @xx{classical,
require(__DIR__ . '/vendor/autoload.php');
$_GET['library'] = 1;

function bibtexbrowser_cff($arguments)
{
    $db = new \BibtexBrowser\BibtexBrowser\BibDataBase();
    $db->load($arguments[1]);
    $current_entry = null;
    $current_field = null;
    $argumentsCount = count($arguments);
    for ($i = 2; $i < $argumentsCount; $i++) {
        $arg = $arguments[$i];
        if ($arg === '--id') {
            $current_entry = $db->getEntryByKey($arguments[$i + 1]);
        }
    }
    echo $current_entry->toCFF();
}

bibtexbrowser_cff($argv);
