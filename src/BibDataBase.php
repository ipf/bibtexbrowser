<?php

namespace BibtexBrowser\BibtexBrowser;

use BibtexBrowser\BibtexBrowser\Configuration\Configuration;
use BibtexBrowser\BibtexBrowser\Parser\BibDBBuilder;

/** represents a bibliographic database that contains a set of bibliographic entries.
 * usage:
 * <pre>
 * $db = new BibDataBase();
 * $db->load('bibacid-utf8.bib');
 * $query = array('author'=>'martin', 'year'=>2008);
 * foreach ($db->multisearch($query) as $bibentry) { echo $bibentry->getTitle(); }
 * </pre>
 */
class BibDataBase
{
    /** A hash table from keys (e.g. Goody1994) to bib entries (BibEntry instances). */
    public array $bibdb;

    /** A hashtable of constant strings */
    public array $stringdb;

    /** A list of file names */
    public ?array $from_files = null;

    /** Creates a new database by parsing bib entries from the given
     * file. (backward compatibility) */
    public function load($filename)
    {
        $this->from_files[] = $filename;
        $this->update($filename);
    }

    /** Updates a database (replaces the new bibtex entries by the most recent ones) */
    public function update($filename): void
    {
        $this->from_files[] = $filename;
        $this->update_internal($filename, null);
    }

    /** returns true if this file is already loaded in this BibDataBase object */
    public function is_already_loaded($filename)
    {
        return in_array($filename, $this->from_files);
    }

    /** See update */
    public function update_internal($resource_name, $resource): void
    {
        $empty_array = [];
        $db = new BibDBBuilder();
        $db->build($resource_name, $resource);

        $this->stringdb = array_merge($this->stringdb, $db->stringdb);

        $result = $db->builtdb;


        foreach ($result as $b) {
            // new entries:
            if (!isset($this->bibdb[$b->getKey()])) {
                //echo 'adding...<br/>';
                $this->addEntry($b);
            } // update entry
            elseif (isset($this->bibdb[$b->getKey()]) && ($b->getText() !== $this->bibdb[$b->getKey()]->getText())) {
                //echo 'replacing...<br/>';
                $this->bibdb[$b->getKey()] = $b;
            }
        }

        // some entries have been removed
        foreach ($this->bibdb as $e) {
            if (!isset($result[$e->getKey()])
                && $e->filename == $resource_name // bug reported by Thomas on Dec 4 2012
            ) {
                //echo 'deleting...<br/>';
                unset($this->bibdb[$e->getKey()]);
            }
        }

        // some @string have been removed
        foreach ($this->stringdb as $k => $e) {
            if (!isset($db->stringdb[$k])
                && $e->filename == $resource_name) {
                //echo 'deleting...<br/>';
                unset($this->stringdb[$e->name]);
            }
        }
    }

    /** Creates a new empty database */
    public function __construct()
    {
        $this->bibdb = [];
        $this->stringdb = [];
    }

    /** Returns the $n latest modified bibtex entries/ */
    public function getLatestEntries(?int $n): array
    {
        $order = 'compare_bib_entry_by_mtime';
        $array = $this->bibdb; // array passed by value
        uasort($array, $order);
        return array_slice($array, 0, $n);
    }

    /** Returns all entries as an array. Each entry is an instance of
     * class BibEntry. */
    public function getEntries(): array
    {
        return $this->bibdb;
    }

    /** tests whether the database contains a bib entry with $key */
    public function contains($key): bool
    {
        return isset($this->bibdb[$key]);
    }

    /** Returns all entries categorized by types. The returned value is
     * a hashtable from types to arrays of bib entries.
     */
    public function getEntriesByTypes(): array
    {
        $result = [];
        foreach ($this->bibdb as $b) {
            $result[$b->getType()][] = $b;
        }

        return $result;
    }

    /** Returns an array containing all the bib types (strings). */
    public function getTypes()
    {
        $result = [];
        foreach ($this->bibdb as $b) {
            $result[$b->getType()] = 1;
        }

        return array_keys($result);
    }

    /** Generates and returns an array consisting of all authors.
     * The returned array is a hash table with keys <FirstName LastName>
     * and values <LastName, FirstName>.
     */
    public function authorIndex(): array
    {
        $tmp = [];
        foreach ($this->bibdb as $bib) {
            foreach ($bib->getFormattedAuthorsArray() as $a) {
                $a = strip_tags($a);
                //we use an array because several authors can have the same lastname
                @$tmp[$bib->getLastName($a)] = $a;
            }
        }

        ksort($tmp);
        $result = [];
        foreach ($tmp as $k => $v) {
            $result[$v] = $v;
        }

        return $result;
    }

    /** Generates and returns an array consisting of all tags.
     */
    public function tagIndex(): array
    {
        $result = [];
        foreach ($this->bibdb as $bib) {
            if (!$bib->hasField('keywords')) {
                continue;
            }

            $tags = $bib->getKeywords();
            foreach ($tags as $a) {
                $ta = trim($a);
                $result[$ta] = $ta;
            }
        }

        asort($result);
        return $result;
    }

    /**
     * Generates and returns an array consisting of all years.
     */
    public function yearIndex(): array
    {
        $result = [];
        foreach ($this->bibdb as $bib) {
            if (!$bib->hasField('year')) {
                continue;
            }

            $year = strtolower($bib->getYearRaw());
            $yearInt = (int)$year;

            $key = match ($year) {
                (string)$yearInt => $year,
                Configuration::Q_YEAR_INPRESS => PHP_INT_MAX + Configuration::ORDER_YEAR_INPRESS,
                Configuration::Q_YEAR_ACCEPTED => PHP_INT_MAX + Configuration::ORDER_YEAR_ACCEPTED,
                Configuration::Q_YEAR_SUBMITTED => PHP_INT_MAX + Configuration::ORDER_YEAR_SUBMITTED,
                default => PHP_INT_MAX + Configuration::ORDER_YEAR_OTHERNONINT,
            };

            $result[$key] = $year;
        }

        krsort($result);
        return $result;
    }

    /** Given its key, return the bib entry. */
    public function getEntryByKey($key)
    {
        return $this->bibdb[$key];
    }

    /** Adds a new bib entry to the database. */
    public function addEntry($entry): void
    {
        if (!$entry->hasField('key')) {
            throw new \Exception('error: a bibliographic entry must have a key ' . $entry->getText());
        }

        // we keep its insertion order
        $entry->order = count($this->bibdb);
        $this->bibdb[$entry->getKey()] = $entry;
    }


    /**
     * Returns an array containing all bib entries matching the given
     * type.
     */
    public function searchType(string $type): array
    {
        $result = [];
        foreach ($this->bibdb as $bib) {
            if ($bib->getType() == $type) {
                $result[] = $bib;
            }
        }

        return $result;
    }

    /** Returns an array of bib entries (BibEntry) that satisfy the query
     * $query is an hash with entry type as key and searched fragment as value
     */
    public function multisearch($query)
    {
        if (count($query) < 1) {
            return [];
        }

        if (isset($query[Configuration::Q_ALL])) {
            return array_values($this->bibdb);
        }

        $result = [];
        /** @var BibEntry $bib */
        foreach ($this->bibdb as $bib) {
            $entryisselected = true;
            foreach ($query as $field => $fragment) {
                $field = strtolower($field);
                if ($field === Configuration::Q_SEARCH) {
                    // we search in the whole bib entry
                    if (!$bib->hasPhrase($fragment)) {
                        $entryisselected = false;
                        break;
                    }
                } elseif ($field === Configuration::Q_EXCLUDE) {
                    if ($bib->hasPhrase($fragment)) {
                        $entryisselected = false;
                        break;
                    }
                } elseif ($field === Configuration::Q_TYPE || $field === BibEntry::Q_INNER_TYPE) {
                    // types are always exact search
                    // remarks Ken
                    // type:"book" should only select book (and not inbook, book, bookchapter)
                    // this was before in Dispatch:type()
                    // moved here so that it is also used by AcademicDisplay:search2html()
                    if (!$bib->hasPhrase('^(' . $fragment . ')$', BibEntry::Q_INNER_TYPE)) {
                        $entryisselected = false;
                        break;
                    }
                } elseif ($field == Configuration::Q_KEYS) {
                    if (!in_array($bib->getKey(), $query[Configuration::Q_KEYS])) {
                        $entryisselected = false;
                        break;
                    }
                } elseif ($field == Configuration::Q_RANGE) {
                    $year = $bib->getYear();
                    $withinRange = false;

                    foreach ($query[Configuration::Q_RANGE] as $elements) {
                        if ($elements[0] === '' && $elements[1] === '') {
                            $withinRange = true;
                        } elseif ($elements[0] === '' && $year <= $elements[1]) {
                            $withinRange = true;
                        } elseif ($elements[1] === '' && $year >= $elements[0]) {
                            $withinRange = true;
                        } elseif ($year <= $elements[1] && $year >= $elements[0]) {
                            $withinRange = true;
                        }
                    }

                    if (!$withinRange) {
                        $entryisselected = false;
                    }
                } elseif (!$bib->hasPhrase($fragment, $field)) {
                    $entryisselected = false;
                    break;
                }
            }

            if ($entryisselected) {
                $result[] = $bib;
            }
        }

        return $result;
    }

    /** returns the text of all @String entries of this dabatase */
    public function stringEntriesText()
    {
        $s = '';
        foreach ($this->stringdb as $entry) {
            $s .= $entry->toString() . "\n";
        }

        return $s;
    }

    /** returns a classical textual Bibtex representation of this database */
    public function toBibtex()
    {
        $s = '';
        $s .= $this->stringEntriesText();
        foreach ($this->bibdb as $bibentry) {
            $s .= $bibentry->getText() . "\n";
        }

        return $s;
    }
}
