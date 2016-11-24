<?php

namespace Znck\Sereno\Contracts;

use Symfony\Component\Finder\SplFileInfo;

interface Extractor
{
    public function handles(): array;

    public function get(SplFileInfo $file);
}
