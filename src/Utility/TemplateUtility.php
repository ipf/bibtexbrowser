<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser\Utility;

class TemplateUtility
{

    /**
     * returns an HTML tag depending on BIBTEXBROWSER_LAYOUT e.g. <TABLE>
     */
    public static function get_HTML_tag_for_layout()
    {
        switch (BIBTEXBROWSER_LAYOUT) { /* switch for different layouts */
            case 'list':
                $tag = 'ul';
                break;
            case 'ordered_list':
                $tag = 'ol';
                break;
            case 'table':
                $tag = 'table';
                break;
            case 'definition':
                $tag = 'div';
                break;
            default:
                die('Unknown BIBTEXBROWSER_LAYOUT');
        }

        return $tag;
    }

    /**
     * prints the header of a layouted HTML, depending on BIBTEXBROWSER_LAYOUT e.g. <TABLE>
     */
    public static function print_header_layout()
    {
        if (BIBTEXBROWSER_LAYOUT === 'list') {
            return;
        }

        echo '<' . TemplateUtility::get_HTML_tag_for_layout() . ' class="result">' . "\n";
    }

    /**
     * prints the footer of a layouted HTML, depending on BIBTEXBROWSER_LAYOUT e.g. </TABLE>
     */
    public static function print_footer_layout()
    {
        echo '</' . TemplateUtility::get_HTML_tag_for_layout() . '>';
    }

    /**
     * ^^adds a touch of AJAX in bibtexbrowser to display bibtex entries inline.
     * It uses the HIJAX design pattern: the Javascript code fetches the normal bibtex HTML page
     * and extracts the bibtex.
     * In other terms, URLs and content are left perfectly optimized for crawlers
     * Note how beautiful is this piece of code thanks to JQuery.^^
     */
    public static function javascript()
    {
        // we use jquery with the official content delivery URLs
        // Microsoft and Google also provide jquery with their content delivery networks
        ?>
        <script type="text/javascript" src="<?php echo JQUERY_URI ?>"></script>
        <script type="text/javascript"><!--
          // Javascript progressive enhancement for bibtexbrowser
          $('a.biburl').each(function () { // for each url "[bibtex]"
            var biburl = $(this);
            if (biburl.attr('bibtexbrowser') === undefined) {
              biburl.click(function (ev) { // we change the click semantics
                ev.preventDefault(); // no open url
                if (biburl.nextAll('pre').length === 0) { // we don't have yet the bibtex data
                  var bibtexEntryUrl = $(this).attr('href');
                  $.ajax({
                    url: bibtexEntryUrl, dataType: 'html', success: function (data) { // we download it
                      // elem is the element containing bibtex entry, creating a new element is required for Chrome and IE
                      var elem = $('<pre class="purebibtex"/>');
                      elem.text($('.purebibtex', data).text()); // both text() are required for IE
                      // we add a link so that users clearly see that even with AJAX
                      // there is still one URL per paper.
                      elem.append(
                        $('<div class="bibtex_entry_url">%% Bibtex entry URL: <a href="' + bibtexEntryUrl + '">' + bibtexEntryUrl + '</a></div>')
                      ).appendTo(biburl.parent());
                    }, error: function () {
                      window.location.href = biburl.attr('href');
                    }
                  });
                } else {
                  biburl.nextAll('pre').toggle();
                }  // we toggle the view
              });
              biburl.attr('bibtexbrowser', 'done');
            }
          });


          --></script><?php
    }

    /** encapsulates the content of a delegate into full-fledged HTML (&lt;HTML>&lt;BODY> and TITLE)
     * usage:
     * <pre>
     * $db = zetDB('bibacid-utf8.bib');
     * $dis = new BibEntryDisplay($db->getEntryByKey('classical'));
     * HTMLTemplate($dis);
     * </pre>
     * $content: an object with methods
     * display()
     * getRSS()
     * getTitle()
     * $title: title of the page
     */
    public static function HTMLTemplate($content)
    {
        // when we load a page with AJAX
        // the HTTP header is taken into account, not the <meta http-equiv>
        header('Content-type: text/html; charset=' . OUTPUT_ENCODING);
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n"; ?>
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=<?php echo OUTPUT_ENCODING ?>"/>
            <meta name="generator" content="bibtexbrowser v__GITHUB__"/>
            <?php

            // we may add new metadata tags
            $metatags = [];
        if (method_exists($content, 'metadata')) {
            $metatags = $content->metadata();
        }

        foreach ($metatags as $item) {
            list($name, $value) = $item;
            echo '<meta name="' . $name . '" property="' . $name . '" content="' . $value . '"/>' . "\n";
        }

        // now the title
        if (method_exists($content, 'getTitle')) {
            echo '<title>' . strip_tags($content->getTitle()) . '</title>';
        }

        // now the CSS
        echo '<style type="text/css"><!--  ' . "\n";

        if (method_exists($content, 'getCSS')) {
            echo $content->getCSS();
        } elseif (is_readable(__DIR__ . '/bibtexbrowser.css')) {
            readfile(__DIR__ . '/bibtexbrowser.css');
        } else {
            bibtexbrowserDefaultCSS();
        }

        echo "\n" . ' --></style>'; ?>
        </head>
        <body>
        <?php
        // configuration point to add a banner
        echo bibtexbrowser_top_banner(); ?>
        <?php
        if (method_exists($content, 'getTitle')) {
            echo '<div class="rheader">' . $content->getTitle() . '</div>';
        } ?>
        <?php
        $content->display();
        echo poweredby();

        if (c('BIBTEXBROWSER_USE_PROGRESSIVE_ENHANCEMENT')) {
            TemplateUtility::javascript();
        }

        if (BIBTEXBROWSER_RENDER_MATH) {
            javascript_math();
        } ?>
        </body>
        </html>
<?php
    }
}
