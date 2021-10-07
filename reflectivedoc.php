<?php /*
Provides ways to manipulate and print API documentation of PHP programs

author: Martin Monperrus

*/

global $diffs;
$diffs = [];

function get_functions_in($phpfile)
{
    return load($phpfile)['functions'];
}

function load($phpfile)
{
    global $diffs;

    if (!isset($diffs[$phpfile])) {
        $beforef = get_defined_functions()['user'];
        $beforec = get_declared_classes();
    } else {
        return $diffs[$phpfile];
    }

    // prevent problems if they is one exit in the included script
    // register_shutdown_function('printNewFunctions',$beforef);

    // we don't want the output
    ob_start();
    require($phpfile);
    // this does not work because the include is not executed
    ob_end_clean();

    $afterf = get_defined_functions()['user'];
    $afterc = get_declared_classes();

    $new_functions = [];
    foreach ($afterf as $k) {
        if (!in_array($k, $beforef)) {
            $new_functions[] = $k;
        }
    }
    $diffs[$phpfile]['functions'] = $new_functions;

    $new_classes = [];
    foreach ($afterc as $k) {
        if (!in_array($k, $beforec)) {
            $new_classes[] = $k;
        }
    }
    $diffs[$phpfile]['classes'] = $new_classes;

    return $diffs[$phpfile];
}

/** returns a list of new classes */
function get_classes_in($phpfile)
{
    return load($phpfile)['classes'];
}

/** print only documented classes and methods */
function printDocumentedClasses($file)
{
    $res = '';
    foreach (get_classes_in($file) as $klass) {
        $res .= printAPIDocClass($klass, true);
    }
    return $res;
}

/**
 *
 * A wiki parser implemented in a neat way.
 *
 * Author: Martin Monperrus
 * Public domain
 *
 * Usage: echo gk_wiki2html("foo **bar*")
 */

/* Gakoparser

Gakoparser parses a family of markup language

Sunday, October 30 2011
impossible to handle both ===== sdf ===== and ==== sdfsdf ====
handling of space different

handling of >\n different

 */

/** returns a parser object to parse wiki syntax.
 *
 * The returned object may be used with the parse method:
 * <pre>
 * $parser = create_wiki_parser();
 * $html_text = $parser->parse($wiki_text);
 * </pre>
 */
function create_wiki_parser()
{
    $x = new \BibtexBrowser\BibtexBrowser\GakoParser();
    return $x->setDelegate(new \BibtexBrowser\BibtexBrowser\GakowikiMarkupToHTMLTranslator())
        ->addDelimX('comment', '<!--', '-->')->noNesting('comment')
        ->addDelim('bold', '**')
        ->addDelim('italic', '//')//->noNesting('italic')
        ->addDelim('bold', "'''")
        ->addDelim('monotype', "''")->noNesting('monotype')
        ->addDelim('h2', '=====') // the longest comes before, it has the highest priority
        ->addDelim('h3', '====')
        ->addDelim('table', '|||')
        ->addDelimXML('pre', 'pre')->noNesting('pre') // this is essential otherwise you have infinite loops
        ->addDelimX('pre', '{{{', '}}}')->noNesting('pre2') // à la Google Code wiki syntax
        ->addDelimX('link', '[[', ']]')->noNesting('link')
        ->addDelimX('phpcode2', '<?php2wiki', '?>')->noNesting('phpcode2')
        ->addDelimX('phpcode', '<?php', '?>')->noNesting('phpcode')
        ->addDelimX('img2', '<img', '/>')->noNesting('img2')
        ->addDelim('img', '%%')->noNesting('img')// huge bug when I did this for 1000 index :(
        ->addDelimX('script', '<script', '</script>')->noNesting('script')
        ->addDelimX('unwrap', '^^', '^^')
        ->addTag('toc', '+++TOC+++')
        ->addDelimX('a', '<a', '</a>')->noNesting('a') // important to support cross tags

        ->addDelimX('iframe', '<iframe', '</iframe>')->noNesting('iframe')

        // Dec 30 2012
        ->addDelimX('bib', '\bib{', '}')
        ->addDelimX('cite', '\cite{', '}')

        ;
} // end create_wiki_parser

function gakowiki__doc()
{
    ?>
    <a href="http://www.monperrus.net/martin/gakowiki-syntax">Syntax specification</a>:<br/>
    **<b>this is bold</b>**<br/>
    //<i>this is italic</i>//<br/>
    ''<code>this is code</code>''<br/>
    [[link to a page on this wiki]],[[http://www.google.fr|link to google]]<br/>
    <h2>=====Section=====</h2>
    <h3>====Subsection====</h3>
    <?php
}

/** returns an HTML version of the wiki text of $text, according to the syntax of [[http://www.monperrus.net/martin/gakowiki-syntax]] */
function gk_wiki2html($text)
{
    global $parser;
    if ($parser == null) {
        $parser = create_wiki_parser();
    }
    return $parser->parse($text);
}

function printGk($comment): string
{
    try {
        $result = htmlentities($comment);
        $result = str_replace(['&lt;pre&gt;', '&lt;/pre&gt;'], ['<pre>', '</pre>'], $result);
        // removes lines prefixed "*" often used to have nice API comments
        $result = preg_replace('/^.*?\*/m', '', $result);
        return '<pre>' . $result . '</pre>';
    } catch (\BibtexBrowser\BibtexBrowser\GakoParserException $e) {
        return '<pre>' . $comment . '</pre>';
    }
}

/** outputs the API doc of the function called $fname */
function printDocFuncName($fname, $prefix = '')
{
    $funcdeclared = new ReflectionFunction($fname);
    return printDocFuncObj($funcdeclared, $prefix);
}

function getComment($funcdeclared)
{
    return trim(substr($funcdeclared->getDocComment(), 3, -2));
}

function printDocFuncObj($funcdeclared, $prefix = '', $documented = true)
{
    $comment = trim(substr($funcdeclared->getDocComment(), 3, -2));
    if ($documented && strlen($comment) < 1) {
        return '';
    }
    $res = '';
    $res .= '<div>';
    $res .= '<b>' . $prefix . $funcdeclared->getName() . '</b>';
    $res .= '<i>(' . implode(', ', array_map('f', $funcdeclared->getParameters())) . ')</i> ';
    $res .= printGk($comment);
    $res .= '</div>';
    return $res;
}

// Anonymous functions are available only since PHP 5.3.0
function f($x)
{
    return '$' . $x->getName();
}


/** this is printNewFunctions
 * the main limitation is that this does not fully work if there is an exit/die in the included script
 */
function printNewFunctions($beforef)
{
    $afterf = get_defined_functions();
    foreach ($afterf['user'] as $fname) {
        $funcdeclared = new ReflectionFunction($fname);
        if (!in_array($fname, $beforef['user']) && $funcdeclared->getFileName() === realpath($_GET['file'])) {
            printDocFunc($funcdeclared);
        }
    }
}

/** outputs an HTML representation of the API doc of the class called $cname */
function printAPIDocClass($cname, $documented = true)
{
    $res = '';
    $cdeclared = new ReflectionClass($cname);
    $res .= '<b>' . $cdeclared->getName() . '</b> ';
    $comment = trim(substr($cdeclared->getDocComment(), 3, -2));
    if ($documented && strlen($comment) < 1) {
        return '';
    }
    $res .= printGk($comment);
    foreach ($cdeclared->getMethods() as $method) {
        $f = printDocFuncObj($method, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'/*,$cname.'.'*/, true);
        if (strlen($f) > 0) {
            $res .= $f;
        }
    }
    return '<div>' . $res . '</div><hr/>';
}

function getCodeSnippetsInClass($cname)
{
    $res = [];
    $cdeclared = new ReflectionClass($cname);
    $res[] = _getCodeSnippet($cdeclared);
    foreach ($cdeclared->getMethods() as $method) {
        $res[] = _getCodeSnippet($method);
    }
    return $res;
}

/** returns the  snippet of a function */
function getCodeSnippet($function_name)
{
    $funcdeclared = new ReflectionFunction($function_name);
    return _getCodeSnippet($funcdeclared);
}


function _getCodeSnippet($obj)
{
    $comment = getComment($obj);
    if (preg_match('/<pre>(.*)<\/pre>/is', $comment, $matches)) {
        return $matches[1];
    }
    return '';
}


function getAllSnippetsInFile($file)
{
    $res = [];
    foreach (get_functions_in($file) as $f) {
        $x = getCodeSnippet($f);
        if ($x != '') {
            $res[] = $x;
        }
    }

    foreach (get_classes_in($file) as $klass) {
        foreach (getCodeSnippetsInClass($klass) as $x) {
            if ($x != '') {
                $res[] = $x;
            }
        }
    }
    return $res;
}
?>
