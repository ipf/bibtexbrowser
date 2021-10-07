<?php

namespace BibtexBrowser\BibtexBrowser;

/** is a generic parser of bibtex files.
 * usage:
 * <pre>
 * $delegate = new XMLPrettyPrinter();// or another delegate such as BibDBBuilder
 * $parser = new StateBasedBibtexParser($delegate);
 * $parser->parse(fopen('bibacid-utf8.bib','r'));
 * </pre>
 * notes:
 * - It has no dependencies, it can be used outside of bibtexbrowser
 * - The delegate is expected to have some methods, see classes BibDBBuilder and XMLPrettyPrinter
 */
class StateBasedBibtexParser
{
    private const BUFFER_SIZE = 100000;
    public $delegate;

    public function __construct($delegate)
    {
        $this->delegate = $delegate;
    }

    public function parse($handle)
    {
        if (gettype($handle) === 'string') {
            throw new \Exception('oops');
        }
        $delegate = $this->delegate;
        // STATE DEFINITIONS
        @define('NOTHING', 1);
        @define('GETTYPE', 2);
        @define('GETKEY', 3);
        @define('GETVALUE', 4);
        @define('GETVALUEDELIMITEDBYQUOTES', 5);
        @define('GETVALUEDELIMITEDBYQUOTES_ESCAPED', 6);
        @define('GETVALUEDELIMITEDBYCURLYBRACKETS', 7);
        @define('GETVALUEDELIMITEDBYCURLYBRACKETS_ESCAPED', 8);
        @define('GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL', 9);
        @define('GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL_ESCAPED', 10);
        @define('GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL', 11);
        @define('GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL_ESCAPED', 12);
        @define('GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL', 13);
        @define('GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL_ESCAPED', 14);


        $state = NOTHING;
        $entrytype = '';
        $entrykey = '';
        $entryvalue = '';
        $fieldvaluepart = '';
        $finalkey = '';
        $entrysource = '';

        // metastate
        $isinentry = false;

        $delegate->beginFile();

        // if you encounter this error "Allowed memory size of xxxxx bytes exhausted"
        // then decrease the size of the temp buffer below
        $bufsize = self::BUFFER_SIZE;
        while (!feof($handle)) {
            $sread = fread($handle, $bufsize);
            //foreach(str_split($sread) as $s) {
            for ($i = 0, $iMax = strlen($sread); $i < $iMax; $i++) {
                $s = $sread[$i];

                if ($isinentry) {
                    $entrysource .= $s;
                }

                if ($state == NOTHING) {
                    // this is the beginning of an entry
                    if ($s == '@') {
                        $delegate->beginEntry();
                        $state = GETTYPE;
                        $isinentry = true;
                        $entrysource = '@';
                    }
                } elseif ($state == GETTYPE) {
                    // this is the beginning of a key
                    if ($s == '{') {
                        $state = GETKEY;
                        $delegate->setEntryType($entrytype);
                        $entrytype = '';
                    } else {
                        $entrytype .= $s;
                    }
                } elseif ($state == GETKEY) {
                    // now we get the value
                    if ($s == '=') {
                        $state = GETVALUE;
                        $fieldvaluepart = '';
                        $finalkey = $entrykey;
                        $entrykey = '';
                    } // oups we only have the key :-) anyway
                    elseif ($s == '}') {
                        $state = NOTHING;
                        $isinentry = false;
                        $delegate->endEntry($entrysource);
                        $entrykey = '';
                    } // OK now we look for values
                    elseif ($s == ',') {
                        $state = GETKEY;
                        $delegate->setEntryKey($entrykey);
                        $entrykey = '';
                    } else {
                        $entrykey .= $s;
                    }
                }
                // we just got a =, we can now receive the value, but we don't now whether the value
                // is delimited by curly brackets, double quotes or nothing
                elseif ($state == GETVALUE) {

                    // the value is delimited by double quotes
                    if ($s == '"') {
                        $state = GETVALUEDELIMITEDBYQUOTES;
                    } // the value is delimited by curly brackets
                    elseif ($s == '{') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS;
                    } // the end of the key and no value found: it is the bibtex key e.g. \cite{Descartes1637}
                    elseif ($s == ',') {
                        $state = GETKEY;
                        $delegate->setEntryField($finalkey, $entryvalue);
                        $entryvalue = ''; // resetting the value buffer
                    } // this is the end of the value AND of the entry
                    elseif ($s == '}') {
                        $state = NOTHING;
                        $delegate->setEntryField($finalkey, $entryvalue);
                        $isinentry = false;
                        $delegate->endEntry($entrysource);
                        $entryvalue = ''; // resetting the value buffer
                    } elseif ($s == ' ' || $s == "\t" || $s == "\n" || $s == "\r") {
                        // blank characters are not taken into account when values are not in quotes or curly brackets
                    } else {
                        $entryvalue .= $s;
                    }
                } /* GETVALUEDELIMITEDBYCURLYBRACKETS* handle entries delimited by curly brackets and the possible nested curly brackets */
                elseif ($state == GETVALUEDELIMITEDBYCURLYBRACKETS) {
                    if ($s == '\\') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_ESCAPED;
                        $entryvalue .= $s;
                    } elseif ($s == '{') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL;
                        $entryvalue .= $s;
                        $delegate->entryValuePart($finalkey, $fieldvaluepart, 'CURLYTOP');
                        $fieldvaluepart = '';
                    } elseif ($s == '}') { // end entry
                        $state = GETVALUE;
                        $delegate->entryValuePart($finalkey, $fieldvaluepart, 'CURLYTOP');
                    } else {
                        $entryvalue .= $s;
                        $fieldvaluepart .= $s;
                    }
                } // handle anti-slashed brackets
                elseif ($state == GETVALUEDELIMITEDBYCURLYBRACKETS_ESCAPED) {
                    $state = GETVALUEDELIMITEDBYCURLYBRACKETS;
                    $entryvalue .= $s;
                } // in first level of curly bracket
                elseif ($state == GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL) {
                    if ($s == '\\') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL_ESCAPED;
                        $entryvalue .= $s;
                    } elseif ($s == '{') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL;
                        $entryvalue .= $s;
                    } elseif ($s == '}') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS;
                        $delegate->entryValuePart($finalkey, $fieldvaluepart, 'CURLYONE');
                        $fieldvaluepart = '';
                        $entryvalue .= $s;
                    } else {
                        $entryvalue .= $s;
                        $fieldvaluepart .= $s;
                    }
                } // handle anti-slashed brackets
                elseif ($state == GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL_ESCAPED) {
                    $state = GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL;
                    $entryvalue .= $s;
                } // in second level of curly bracket
                elseif ($state == GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL) {
                    if ($s == '\\') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL_ESCAPED;
                        $entryvalue .= $s;
                    } elseif ($s == '{') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL;
                        $entryvalue .= $s;
                    } elseif ($s == '}') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL;
                        $entryvalue .= $s;
                    } else {
                        $entryvalue .= $s;
                    }
                } // handle anti-slashed brackets
                elseif ($state == GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL_ESCAPED) {
                    $state = GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL;
                    $entryvalue .= $s;
                } // in third level of curly bracket
                elseif ($state == GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL) {
                    if ($s == '\\') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL_ESCAPED;
                        $entryvalue .= $s;
                    } elseif ($s == '}') {
                        $state = GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL;
                        $entryvalue .= $s;
                    } else {
                        $entryvalue .= $s;
                    }
                } // handle anti-slashed brackets
                elseif ($state == GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL_ESCAPED) {
                    $state = GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL;
                    $entryvalue .= $s;
                } /* handles entries delimited by double quotes */
                elseif ($state == GETVALUEDELIMITEDBYQUOTES) {
                    if ($s == '\\') {
                        $state = GETVALUEDELIMITEDBYQUOTES_ESCAPED;
                        $entryvalue .= $s;
                    } elseif ($s == '"') {
                        $state = GETVALUE;
                    } else {
                        $entryvalue .= $s;
                    }
                } // handle anti-double quotes
                elseif ($state == GETVALUEDELIMITEDBYQUOTES_ESCAPED) {
                    $state = GETVALUEDELIMITEDBYQUOTES;
                    $entryvalue .= $s;
                }
            } // end for
        } // end while
        $delegate->endFile();
        //$d = $this->delegate;print_r($d);
    } // end function
} // end class
