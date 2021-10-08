<?php

namespace BibtexBrowser\BibtexBrowser\Display;

class SimpleDisplayExt extends SimpleDisplay
{
    public function setIndices()
    {
        $this->setIndicesInIncreasingOrderChangingEveryYear();
    }
}
