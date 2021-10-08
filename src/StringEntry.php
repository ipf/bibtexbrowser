<?php

declare(strict_types=1);

namespace BibtexBrowser\BibtexBrowser;

/** represents @string{k=v} */
class StringEntry
{
    public function __construct(public string $name, public string $value, public string $filename)
    {
    }

    public function toString()
    {
        return '@string{' . $this->name . '={' . $this->value . '}}';
    }
}
