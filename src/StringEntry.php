<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** represents @string{k=v} */
class StringEntry
{
    public $name;

    public $value;

    public $filename;

    public function __construct($k, $v, $filename)
    {
        $this->name = $k;
        $this->value = $v;
        $this->filename = $filename;
    }

    public function toString()
    {
        return '@string{' . $this->name . '={' . $this->value . '}}';
    }
}

 // end class StringEntry
