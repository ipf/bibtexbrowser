<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser\Gako;

use BibtexBrowser\BibtexBrowser\Delimiter;

/** provides a parametrizable parser. The main method is "parse" */
class GakoParser
{

    /**
     * @var mixed[]|\Delimiter[]|mixed
     */
    public array $start_delimiters;

    /**
     * @var mixed[]|\Delimiter[]|mixed
     */
    public array $end_delimiters;

    public array $nonesting;

    /**
     * @var mixed[]|mixed
     */
    public $delegate;

    /** is the PHP5 constructor. */
    public function __construct()
    {
        // an array of Delimiter objects
        $this->start_delimiters = [];
        $this->end_delimiters = [];
        $this->nonesting = [];
        $this->delegate = [];
    }

    /** specifies that $tag can not contain nested tags */
    public function noNesting($tag)
    {
        $this->nonesting[] = $tag;
        return $this;
    }

    public function setDelegate($obj)
    {
        $this->delegate = $obj;
        return $this;
    }

    public function addDelim($name, $delim)
    {
        return $this->addDelimX($name, $delim, $delim);
    }

    /** adds a trigger $tag -> execution of function $name */
    public function addTag($name, $tag)
    {
        $start = substr($tag, 0, -1);
        $end = substr($tag, strlen($tag) - 1);
        return $this->addDelimX($name, $start, $end);
    }

    /** setDelegate must be called before this method */
    public function addDelimX($name, $start, $end)
    {
        if (!method_exists($this->delegate, $name)) {
            throw new GakoParserException('no method ' . $method . ' in delegate!');
        }

        $x = new Delimiter();
        $x->value = $start;
//     $x->type = 'start';
        $x->action = $name;

        $y = new Delimiter();
        $y->value = $end;
//     $y->type = 'end';
        $y->action = $name;

        // crossing links
        $y->startDelim = $x;
        $x->endDelim = $y;

        if (in_array($start, $this->get_start_delimiters())) {
            throw new GakoParserException('delimiter ' . $start . ' already exists');
        }

        $this->start_delimiters[$start] = $x;
        $this->end_delimiters[$end] = $y;
        return $this;
    }

    public function get_start_delimiters()
    {
        return array_keys($this->start_delimiters);
    }

    public function get_end_delimiters()
    {
        return array_keys($this->end_delimiters);
    }

    public function array_preg_quote($x)
    {
        $result = [];
        foreach ($x as $k) {
            $result[] = preg_quote($k, '/');
        }

        return $result;
    }

    public function addDelimXML($name, $delim)
    {
        return $this->addDelimX($name, '<' . $delim . '>', '</' . $delim . '>');
    }

    public function getCandidateDelimiters_man($str)
    {
        $result = [];

        for ($i = 0; $i < strlen($str); ++$i) {
            foreach (array_merge($this->get_start_delimiters(), $this->get_end_delimiters()) as $v) {
                $new_fragment = substr($str, $i, strlen($v));
                if ($new_fragment === $v) {
                    $x = [];
                    $x[0] = [];
                    $x[0][0] = $v;
                    $x[0][1] = $i;
                    $result[] = $x;
                }
            }
        }

        //print_r($result);
        return $result;
    }

    public function getCandidateDelimiters($str)
    {
        //return $this->getCandidateDelimiters_man($str);
        return $this->getCandidateDelimiters_preg($str);
    }

    public function getCandidateDelimiters_preg($str)
    {
        // getting the start delimiters
        preg_match_all(
            '/' . implode('|', array_merge(
                $this->array_preg_quote($this->get_start_delimiters()),
                $this->array_preg_quote($this->get_end_delimiters())
            )) . '/',
            $str,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );
        return $matches;
    }

    public function parse($str)
    {
//     echo "---------parse $str\n";
        $method = '__pre';
        if (method_exists($this->delegate, $method)) {
            $str = $this->delegate->$method($str);
        }

        $matches = $this->getCandidateDelimiters($str);

        // the stack contains the current markup environment
        $init = new Delimiter();
        $init->action = '__init__';
        $init->value = '__init__';
        $init->endDelim = $init;
        $stack = [$init];
        $strings = [1 => ''];
        $last = 0;

        // for each tags found
        foreach ($matches as $_) {
            list($v, $pos) = $_[0];

            // if $_ is a start delimiter,
            // we'll get '' in $k
            if (isset($this->end_delimiters[$v])
                && $stack[0]->endDelim->value == $v
                && $pos >= $last) {
                $k = $stack[0]->endDelim->action;
                $strings[count($stack)] .= substr($str, $last, $pos - $last);
                $closedtag = array_shift($stack);
                $method = $k;
                $value = $strings[count($stack) + 1];
                $transformed = $this->delegate->$method($value);
                $strings[count($stack)] .= $transformed;
                $strings[count($stack) + 1] = '';
                $last = $pos + strlen($v);
            } elseif (!in_array($stack[0]->action, $this->nonesting)
                && in_array($v, $this->get_start_delimiters()) && $pos >= $last) {
                $delim = $this->start_delimiters[$v];
                $k = $delim->action;
                if ($pos > $last) {
                    $strings[count($stack)] .= substr($str, $last, $pos - $last);
                }

                array_unshift($stack, $delim);
                // init the new stack
                $strings[count($stack)] = '';
                $last = $pos + strlen($v);
            }
        }


        if ($stack[0]->action !== '__init__') {
            //print_r($strings);
            throw new GakoParserException('parsing error: ending with ' . $stack[0]->action . " ('" . $strings[1] . "')");
        }

        $result = $strings[count($stack)];

        // adding the rest
        if ($last < strlen($str)) {
            $result .= substr($str, $last, strlen($str) - $last);
        }

        $method = '__post';
        if (method_exists($this->delegate, $method)) {
            $result = $this->delegate->$method($result);
        }

        return $result;
    }
}
