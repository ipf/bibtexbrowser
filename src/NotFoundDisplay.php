<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** handles queries with no result */
class NotFoundDisplay
{
    public function display()
    {
        echo '<span class="count">'.__('Sorry, no results for this query').'</span>';
    }
}
