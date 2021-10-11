<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser\Display;

use BibtexBrowser\BibtexBrowser\Utility\InternationalizationUtility;

/** handles queries with no result */
class NotFoundDisplay implements DisplayInterface
{
    public function display(): void
    {
        echo '<span class="count">' . InternationalizationUtility::translate('Sorry, no results for this query') . '</span>';
    }

    public function setEntries($entries)
    {
        // TODO: Implement setEntries() method.
    }
}
