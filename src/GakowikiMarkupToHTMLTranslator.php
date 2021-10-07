<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** Defines a set of functions for interpreting a markup language in HTML.
 * Maintains a state to build the table of contents.
 * Delegate class for Gakoparser.
 * <pre>
 *
 * // high level
 * $parser = create_wiki_parser();
 * $html_text = $parser->parse($wiki_text);
 *
 * // low level
 * $parser = new Gakoparser();
 * $parser->setDelegate(new MarkupInterpreter());
 * $parser->addDelim('bold','**');
 * echo $parser->parse('hello **world**');
 *
 * </pre>
 */
class GakowikiMarkupToHTMLTranslator
{
    public array $toc = [];

    public array $references = [];

    /** replaces all line breaks by "__newline__" that are meant to replaced back by a call to __post() */
    public function __pre($str)
    {
        $result = $str;

        // we often use nelines to have pretty HTML code
        // such as in tables
        // however, they are no "real" newlines to be transformed in <br/>
        $result = preg_replace("/>\s*(\n|\r\n)/", '>__newline__', $result);
        return $result;
    }

    public function bib($str)
    {
        $this->references[] = $str;
        return '<a name="ref' . count($this->references) . '">[' . count($this->references) . ']</a> ' . $str;
    }

    public function cite($str)
    {
        return '@@@' . $str . '@@@';
    }

    public function escape_newline($str)
    {
        return preg_replace("/(\n|\r\n)/", '__newline__', $str);
    }

    public function toc($str)
    {
        return '+++TOC+++';
    }

    public function __post($str)
    {
        $result = $str;
        $result = preg_replace("/(\n|\r\n)/", "<br/>\n", $result);

        // workaround to support the semantics change in pre mode
        // and the semantics of embedded HTML
        $result = str_replace('__newline__', "\n", $result);// must be at the end

        // cleaning the additional <br>
        // this is really nice
        $result = preg_replace("/(<\/h.>)<br\/>/i", '\\1 ', $result);

        // adding the table of contents
        $result = str_replace($this->toc(''), implode('<br/>', $this->toc), $result);

        // adding the references
        $citeregexp = '/@@@(.*?)@@@/';
        if (preg_match_all($citeregexp, $result, $matches)) {
            foreach ($matches[1] as $m) {
                $theref = '';
                foreach ($this->references as $k => $ref) {
                    if (preg_match('/' . preg_quote($m, '/') . '/i', $ref)) {
                        //echo $m.' '.$ref;
                        // if we have already a match it is not deterministic
                        if ($theref != '') {
                            $result = 'undeterministic citation: ' . $m;
                        }
                        $theref = $ref;
                        $result = preg_replace(
                            '/@@@' . preg_quote($m, '/') . '@@@/i',
                            '<a href="#ref' . ($k + 1) . '">[' . ($k + 1) . ']</a>',
                            $result
                        );
                    }
                }
            }
        }

        return $result;
    }

    /** adds <pre> tags and prevents newline to be replaced by <br/> by __post */
    public function pre($str)
    {
        return '<pre>' . $this->escape_newline($str) . '</pre>';
    }

    /** prevents newline to be replaced by <br/> by __post */
    public function unwrap($str)
    {
        return $this->escape_newline($str);
    }

    /** adds <b> tags */
    public function bold($str)
    {
        return '<b>' . $str . '</b>';
    }

    /** adds <i> tags */
    public function italic($str)
    {
        return '<i>' . $str . '</i>';
    }

    public function table($str)
    {
        $result = '';
        foreach (explode("\n", $str) as $line) {
            if (strlen(trim($line)) > 0) {
                $result .= '<tr>';
                foreach (explode('&&', $line) as $field) {
                    $result .= '<td>' . $field . '</td>';
                }
                $result .= '</tr>';
            }
        }

        return '<table border="1">' . $result . '</table>';
    }


    public function __create_anchor($m)
    {
        return preg_replace('/[^a-zA-Z]/', '', $m);
    }

    public function h2($str)
    {
        $tag = $this->__create_anchor($str);
        $this->toc[] = '<a href="#' . $tag . '">' . $str . '</a>';
        return '<a name="' . $tag . '"></a>' . '<h2>' . $str . '</h2>';
    }

    public function h3($str)
    {
        $tag = $this->__create_anchor($str);
        $this->toc[] = '&nbsp;&nbsp;<a href="#' . $tag . '">' . $str . '</a>';
        return '<a name="' . $tag . '"></a>' . '<h3>' . $str . '</h3>';
    }

    public function monotype($str)
    {
        return '<code>' . str_replace('<', '&lt;', $str) . '</code>';
    }

    public function link($str)
    {
        if (preg_match('/(.*)\|(.*)/', $str, $matches)) {
            $rawurl = $matches[1];
            $text = $matches[2];
        } else {
            $rawurl = $str;
            $text = $str;
        }

        $url = $rawurl;

        if (!preg_match('/(#|^http|^mailto)/', $rawurl)) {
            $url = function_exists('logical2url') ? logical2url($rawurl) : $rawurl;
        }

        return '<a href="' . trim($url) . '">' . trim($text) . '</a>';
    }

    public function phpcode($str)
    {
        ob_start();
        eval($str);
        return $this->escape_newline(ob_get_clean());
    }

    public function phpcode2($str)
    {
        return gk_wiki2html($this->phpcode($str));
    }

    public function a($str)
    {
        return '<a' . $str . '</a>';
    }

    public function script($str)
    {
        return '<script' . $this->escape_newline($str) . '</script>';
    }

    public function img($str)
    {
        return '<img src="' . $this->escape_newline($str) . '"/>';
    }

    public function img2($str)
    {
        return '<img' . $str . '/>';
    }


    public function html($str)
    {
        return '<' . $str . '>';
    }

    public function iframe($str)
    {
        return '<iframe' . $str . '</iframe>';
    }


    public function comment($str)
    {
        return ''; // comments are discarded
    }

    public function silent($str)
    {
        return '';
    }
} // end class
