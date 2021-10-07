<?php
/** PhPUnit tests for bibtexbrowser
 *
 * To run them:
 * $ phpunit BibtexbrowserTest.php
 *
 * With coverage:
 * $ phpunit --coverage-html ./coverage BibtexbrowserTest.php
 *
 * (be sure that xdebug is enabled: /etc/php5/cli/conf.d# ln -s ../../mods-available/xdebug.ini)
 */

function exception_error_handler($severity, $message, $file, $line)
{
    if ($severity != E_ERROR) {
        //trigger_error($message);
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}

error_reporting(E_ALL);

// setup
@copy('bibtexbrowser.local.php', 'bibtexbrowser.local.php.bak');
@unlink('bibtexbrowser.local.php');

define('BIBTEXBROWSER_MAIN', \BibtexBrowser\BibtexBrowser\Nothing::class);

set_error_handler('exception_error_handler');

require(__DIR__ . '/reflectivedoc.php');
$nsnippet = 0;
foreach (getAllSnippetsInFile('bibtexbrowser.php') as $snippet) {
    ob_start();
    eval($snippet);
    ob_get_clean();
    unset($_GET['bib']);
    $nsnippet++;
}
if ($nsnippet != 19) {
    die('oops ' . $nsnippet);
}
restore_error_handler();

@copy('bibtexbrowser.local.php.bak', 'bibtexbrowser.local.php');
