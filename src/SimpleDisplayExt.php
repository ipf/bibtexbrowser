<?php
namespace BibtexBrowser\BibtexBrowser;

class SimpleDisplayExt extends \BibtexBrowser\BibtexBrowser\SimpleDisplay
{
    public function setIndices()
    {
        $this->setIndicesInIncreasingOrderChangingEveryYear();
    }
}
