<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser\Display;

/** is used to create an RSS feed.
 * usage:
 * <pre>
 * $db = zetDB('bibacid-utf8.bib');
 * $query = array('year'=>2005);
 * $rss = new RSSDisplay();
 * $entries = $db->getLatestEntries(10);
 * $rss->setEntries($entries);
 * $rss->display();
 * </pre>
 */
class RSSDisplay implements DisplayInterface
{
    public $entries;

    public string $title = 'RSS produced by bibtexbrowser';

    public function __construct()
    {
        // nothing by default
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /** tries to always output a valid XML/RSS string
     * based on OUTPUT_ENCODING, HTML tags, and the transformations
     * that happened in latex2html */
    public function text2rss($desc)
    {
        // first strip HTML tags
        $desc = strip_tags($desc);

        // some entities may still be here, we remove them
        // we replace html entities e.g. &eacute; by nothing
        // however XML entities are kept (e.g. &#53;)
        $desc = preg_replace('#&\w+;#', '', $desc);

        // bullet proofing ampersand
        $desc = preg_replace('#&([^\#])#', '&#38;$1', $desc);

        // be careful of <
        $desc = str_replace('<', '&#60;', $desc);

        // final test with encoding:
        // (PHP 4 >= 4.4.3, PHP 5 >= 5.1.3)
        if (function_exists('mb_check_encoding') && !mb_check_encoding($desc, OUTPUT_ENCODING)) {
            return 'encoding error: please check the content of OUTPUT_ENCODING';
        }

        return $desc;
    }

    /** sets the entries to be shown */
    public function setEntries($entries)
    {
        $this->entries = $entries;
    }

    public function setWrapper($x)
    {
        $x->wrapper = 'NoWrapper';
    }

    public function display(): void
    {
        header('Content-type: application/rss+xml');
        echo '<?xml version="1.0" encoding="' . OUTPUT_ENCODING . '"?>';
//?>
        <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
                <title><?php echo $this->title; ?></title>
                <link>
                http://<?php echo @$_SERVER['HTTP_HOST'] . htmlentities(@$_SERVER['REQUEST_URI']); ?></link>
                <atom:link href="http://<?php echo @$_SERVER['HTTP_HOST'] . htmlentities(@$_SERVER['REQUEST_URI']); ?>"
                           rel="self" type="application/rss+xml"/>
                <description></description>
                <generator>bibtexbrowser v__GITHUB__</generator>

                <?php
                foreach ($this->entries as $bibentry) {
                    ?>
                    <item>
                        <title><?php echo $this->text2rss($bibentry->getTitle()); ?></title>
                        <link><?php echo $bibentry->getURL(); ?></link>
                        <description>
                            <?php
                            // we are in XML, so we cannot have HTML entitites
                            echo $this->text2rss(bib2html($bibentry) . "\n" . $bibentry->getAbstract()); ?>
                        </description>
                        <guid isPermaLink="false"><?php echo urlencode(@$_GET[Q_FILE] . '::' . $bibentry->getKey()); ?></guid>
                    </item>
                    <?php
                } ?>
            </channel>
        </rss>

<?php
    }
}
