<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** represents @string{k=v} */
class StringEntry
{
    public function __construct(public $name, public $value, public $filename)
    {
    }

    public function toString()
    {
        return '@string{' . $this->name . '={' . $this->value . '}}';
    }
}

 // end class StringEntry
