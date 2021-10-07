<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

class RawBibEntry extends BibEntry
{
    public function setField($name, $value)
    {
        $this->fields[$name] = $value;
        $this->raw_fields[$name] = $value;
    }
}
