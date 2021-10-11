<?php
@define('BIBTEXBROWSER_DEFAULT_FRAME', 'all');
@define('BIBTEXBROWSER_EMBEDDED_WRAPPER', \BibtexBrowser\BibtexBrowser\Utility\TemplateUtility::class.'::HTMLTemplate');

$_GET['bib'] = 'bibacid-utf8.bib';
$_GET['wrapper'] = 'BIBTEXBROWSER_EMBEDDED_WRAPPER';
require(__DIR__ . '/../bibtexbrowser.php');
