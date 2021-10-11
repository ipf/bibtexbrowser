<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser\Utility;

class CharacterUtility
{
    /**
     * converts latex chars to HTML entities.
     * (I still look for a comprehensive translation table from late chars to html, better than [[http://isdc.unige.ch/Newsletter/help.html]])
     */
    public static function latex2html(string $line, bool $do_clean_extra_bracket = true)
    {
        $line = preg_replace('#([^\\\])~#', '\\1&nbsp;', $line);

        $line = str_replace(['---', '--', '``', "''"], ['&mdash;', '&ndash;', '"', '"'], $line);

        // performance increases with this test
        // bug found by Serge Barral: what happens if we have curly braces only (typically to ensure case in Latex)
        // added && strpos($line,'{')===false
        if (!str_contains($line, '\\') && !str_contains($line, '{')) {
            return $line;
        }

        $maths = [];
        $index = 0;
        // first we escape the math env
        preg_match_all('#\$.*?\$#', $line, $matches);
        foreach ($matches[0] as $k) {
            $maths[] = $k;
            $line = str_replace($k, '__MATH' . $index . '__', $line);
            ++$index;
        }

        // we should better replace this before the others
        // in order not to mix with the HTML entities coming after (just in case)
        $line = str_replace(['\\&', '\_', '\%'], ['&amp;', '_', '%'], $line);

        // handling \url{....}
        // often used in howpublished for @misc
        $line = preg_replace('#\\\url\{(.*)\}#U', '<a href="\\1">\\1</a>', $line);

        // Friday, April 01 2011
        // added support for accented i
        // for instance \`\i
        // see http://en.wikibooks.org/wiki/LaTeX/Accents
        // " the letters "i" and "j" require special treatment when they are given accents because it is often desirable to replace the dot with the accent. For this purpose, the commands \i and \j can be used to produce dotless letters."
        $line = preg_replace('#\\\([ij])#i', '\\1', $line);


        $line = self::char2html($line, "'", 'a', 'acute');
        $line = self::char2html($line, "'", 'c', 'acute');
        $line = self::char2html($line, "'", 'e', 'acute');
        $line = self::char2html($line, "'", 'i', 'acute');
        $line = self::char2html($line, "'", 'o', 'acute');
        $line = self::char2html($line, "'", 'u', 'acute');
        $line = self::char2html($line, "'", 'y', 'acute');
        $line = self::char2html($line, "'", 'n', 'acute');

        $line = self::char2html($line, '`', 'a', 'grave');
        $line = self::char2html($line, '`', 'e', 'grave');
        $line = self::char2html($line, '`', 'i', 'grave');
        $line = self::char2html($line, '`', 'o', 'grave');
        $line = self::char2html($line, '`', 'u', 'grave');

        $line = self::char2html($line, '~', 'a', 'tilde');
        $line = self::char2html($line, '~', 'n', 'tilde');
        $line = self::char2html($line, '~', 'o', 'tilde');

        $line = self::char2html($line, '"', 'a', 'uml');
        $line = self::char2html($line, '"', 'e', 'uml');
        $line = self::char2html($line, '"', 'i', 'uml');
        $line = self::char2html($line, '"', 'o', 'uml');
        $line = self::char2html($line, '"', 'u', 'uml');
        $line = self::char2html($line, '"', 'y', 'uml');
        $line = self::char2html($line, '"', 's', 'zlig');

        $line = self::char2html($line, '^', 'a', 'circ');
        $line = self::char2html($line, '^', 'e', 'circ');
        $line = self::char2html($line, '^', 'i', 'circ');
        $line = self::char2html($line, '^', 'o', 'circ');
        $line = self::char2html($line, '^', 'u', 'circ');

        $line = self::char2html($line, 'r', 'a', 'ring');

        $line = self::char2html($line, 'c', 'c', 'cedil');
        $line = self::char2html($line, 'c', 's', 'cedil');
        $line = self::char2html($line, 'v', 's', 'caron');

        $line = str_replace([
            '\\ss',
            '\\o',
            '\\O',
            '\\aa',
            '\\AA',
            '\\l',
            '\\L',
            '\\k{a}',
            '\\\'{c}',
            '\\v{c}',
            '\\v{C}',
            '\\ae'
        ], [
            '&szlig;',
            '&oslash;',
            '&Oslash;',
            '&aring;',
            '&Aring;',
            '&#322',
            '&#321',
            '&#261',
            '&#263',
            '&#269',
            '&#268',
            '&aelig;'
        ], $line);

        // handling \textsuperscript{....} FAILS if there still are nested {}
        $line = preg_replace('#\\\textsuperscript\{(.*)\}#U', '<sup>\\1</sup>', $line);

        // handling \textsubscript{....} FAILS if there still are nested {}
        $line = preg_replace('#\\\textsubscript\{(.*)\}#U', '<sub>\\1</sub>', $line);

        if ($do_clean_extra_bracket) {
            // clean extra tex curly brackets, usually used for preserving capitals
            // must come before the final math replacement
            $line = str_replace(['}', '{'], '', $line);
        }

        // we restore the math env
        foreach ($maths as $i => $math) {
            $line = str_replace('__MATH' . $i . '__', $math, $line);
        }

        return (string) $line;
    }

    /**
     * encodes strings for Z3988 URLs. Note that & are encoded as %26 and not as &amp.
     */
    public static function s3988(?string $s): string
    {
        // first remove the HTML entities (e.g. &eacute;) then urlencode them
        return urlencode($s ?? '');
    }

    public static function char2html_case_sensitive(string $line, $latexmodifier, $char, $entitiyfragment): string
    {
        return (string) preg_replace(
            '/\\{?\\\\' . preg_quote($latexmodifier, '/') . ' ?\\{?' . $char . '\\}?/',
            '&' . $char . '' . $entitiyfragment . ';',
            $line
        );
    }

    /**
     * encapsulates the conversion of a single latex chars to the corresponding HTML entity.
     * It expects a **lower-case** char.
     */
    public static function char2html(string $line, $latexmodifier, $char, $entitiyfragment)
    {
        $line = CharacterUtility::char2html_case_sensitive($line, $latexmodifier, strtoupper($char), $entitiyfragment);
        return CharacterUtility::char2html_case_sensitive($line, $latexmodifier, strtolower($char), $entitiyfragment);
    }

    /**
     * is an extended version of the trim function, removes linebreaks, tabs, etc.
     */
    public static function xtrim(string $line): string
    {
        $line = trim($line);
        // we remove the unneeded line breaks
        // this is *required* to correctly split author lists into names
        // 2010-06-30
        // bug found by Thomas
        // windows new line is **\r\n"** and not the other way around!!
        // according to php.net: Proncess \r\n's first so they aren't converted twice
        $line = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $line);
        // remove superfluous spaces e.g. John+++Bar
        $line = preg_replace('# {2,}#', ' ', $line);
        return (string) $line;
    }
}
