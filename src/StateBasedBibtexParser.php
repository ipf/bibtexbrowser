<?php

namespace BibtexBrowser\BibtexBrowser;

use BibtexBrowser\BibtexBrowser\Parser\ParserDelegateInterface;
use Exception;

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
    /**
     * @var int
     */
    private const NOTHING = 1;

    /**
     * @var int
     */
    private const GETTYPE = 2;

    /**
     * @var int
     */
    private const GETKEY = 3;

    /**
     * @var int
     */
    private const GETVALUE = 4;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYQUOTES = 5;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYQUOTES_ESCAPED = 6;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYCURLYBRACKETS = 7;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYCURLYBRACKETS_ESCAPED = 8;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL = 9;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL_ESCAPED = 10;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL = 11;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL_ESCAPED = 12;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL = 13;

    /**
     * @var int
     */
    private const GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL_ESCAPED = 14;

    /**
     * @var int
     */
    private const BUFFER_SIZE = 100000;

    public function __construct(public ParserDelegateInterface $delegate)
    {
    }

    /**
     * @throws Exception
     */
    public function parse($handle): void
    {
        if (is_string($handle)) {
            throw new Exception('oops');
        }

        $delegate = $this->delegate;

        $state = self::NOTHING;
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
            for ($i = 0, $iMax = strlen($sread); $i < $iMax; ++$i) {
                $s = $sread[$i];

                if ($isinentry) {
                    $entrysource .= $s;
                }

                if ($state === self::NOTHING) {
                    // this is the beginning of an entry
                    if ($s === '@') {
                        $delegate->beginEntry();
                        $state = self::GETTYPE;
                        $isinentry = true;
                        $entrysource = '@';
                    }
                } elseif ($state === self::GETTYPE) {
                    // this is the beginning of a key
                    if ($s === '{') {
                        $state = self::GETKEY;
                        $delegate->setEntryType($entrytype);
                        $entrytype = '';
                    } else {
                        $entrytype .= $s;
                    }
                } elseif ($state === self::GETKEY) {
                    // now we get the value
                    if ($s === '=') {
                        $state = self::GETVALUE;
                        $fieldvaluepart = '';
                        $finalkey = $entrykey;
                        $entrykey = '';
                    } // oups we only have the key :-) anyway
                    elseif ($s === '}') {
                        $state = self::NOTHING;
                        $isinentry = false;
                        $delegate->endEntry($entrysource);
                        $entrykey = '';
                    } // OK now we look for values
                    elseif ($s === ',') {
                        $state = self::GETKEY;
                        $delegate->setEntryKey($entrykey);
                        $entrykey = '';
                    } else {
                        $entrykey .= $s;
                    }
                }
                // we just got a =, we can now receive the value, but we don't now whether the value
                // is delimited by curly brackets, double quotes or nothing
                elseif ($state === self::GETVALUE) {

                    // the value is delimited by double quotes
                    if ($s === '"') {
                        $state = self::GETVALUEDELIMITEDBYQUOTES;
                    } // the value is delimited by curly brackets
                    elseif ($s === '{') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS;
                    } // the end of the key and no value found: it is the bibtex key e.g. \cite{Descartes1637}
                    elseif ($s === ',') {
                        $state = self::GETKEY;
                        $delegate->setEntryField($finalkey, $entryvalue);
                        $entryvalue = ''; // resetting the value buffer
                    } // this is the end of the value AND of the entry
                    elseif ($s === '}') {
                        $state = self::NOTHING;
                        $delegate->setEntryField($finalkey, $entryvalue);
                        $isinentry = false;
                        $delegate->endEntry($entrysource);
                        $entryvalue = ''; // resetting the value buffer
                    } elseif ($s === ' ' || $s === "\t" || $s === "\n" || $s === "\r") {
                        // blank characters are not taken into account when values are not in quotes or curly brackets
                    } else {
                        $entryvalue .= $s;
                    }
                } /* GETVALUEDELIMITEDBYCURLYBRACKETS* handle entries delimited by curly brackets and the possible nested curly brackets */
                elseif ($state === self::GETVALUEDELIMITEDBYCURLYBRACKETS) {
                    if ($s === '\\') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_ESCAPED;
                        $entryvalue .= $s;
                    } elseif ($s === '{') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL;
                        $entryvalue .= $s;
                        $delegate->entryValuePart($finalkey, $fieldvaluepart, 'CURLYTOP');
                        $fieldvaluepart = '';
                    } elseif ($s === '}') {
                        $state = self::GETVALUE;
                        $delegate->entryValuePart($finalkey, $fieldvaluepart, 'CURLYTOP');
                    } else {
                        $entryvalue .= $s;
                        $fieldvaluepart .= $s;
                    }
                } elseif ($state === self::GETVALUEDELIMITEDBYCURLYBRACKETS_ESCAPED) {
                    $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS;
                    $entryvalue .= $s;
                } // in first level of curly bracket
                elseif ($state === self::GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL) {
                    if ($s === '\\') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL_ESCAPED;
                        $entryvalue .= $s;
                    } elseif ($s === '{') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL;
                        $entryvalue .= $s;
                    } elseif ($s === '}') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS;
                        $delegate->entryValuePart($finalkey, $fieldvaluepart, 'CURLYONE');
                        $fieldvaluepart = '';
                        $entryvalue .= $s;
                    } else {
                        $entryvalue .= $s;
                        $fieldvaluepart .= $s;
                    }
                } // handle anti-slashed brackets
                elseif ($state === self::GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL_ESCAPED) {
                    $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL;
                    $entryvalue .= $s;
                } // in second level of curly bracket
                elseif ($state === self::GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL) {
                    if ($s === '\\') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL_ESCAPED;
                    } elseif ($s === '{') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL;
                    } elseif ($s === '}') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_1NESTEDLEVEL;
                    }

                    $entryvalue .= $s;
                } // handle anti-slashed brackets
                elseif ($state === self::GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL_ESCAPED) {
                    $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL;
                    $entryvalue .= $s;
                } // in third level of curly bracket
                elseif ($state === self::GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL) {
                    if ($s === '\\') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL_ESCAPED;
                    } elseif ($s === '}') {
                        $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_2NESTEDLEVEL;
                    }

                    $entryvalue .= $s;
                } // handle anti-slashed brackets
                elseif ($state === self::GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL_ESCAPED) {
                    $state = self::GETVALUEDELIMITEDBYCURLYBRACKETS_3NESTEDLEVEL;
                    $entryvalue .= $s;
                } /* handles entries delimited by double quotes */
                elseif ($state === self::GETVALUEDELIMITEDBYQUOTES) {
                    if ($s === '\\') {
                        $state = self::GETVALUEDELIMITEDBYQUOTES_ESCAPED;
                        $entryvalue .= $s;
                    } elseif ($s === '"') {
                        $state = self::GETVALUE;
                    } else {
                        $entryvalue .= $s;
                    }
                } // handle anti-double quotes
                elseif ($state === self::GETVALUEDELIMITEDBYQUOTES_ESCAPED) {
                    $state = self::GETVALUEDELIMITEDBYQUOTES;
                    $entryvalue .= $s;
                }
            }
        }

        $delegate->endFile();
    }
}
