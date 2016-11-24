<?php

namespace Znck\Sereno\Contracts;

use Symfony\Component\Finder\SplFileInfo;

interface Processor
{
    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @param array                                 $data
     * @param array                                 $options
     *
     * @return $this
     */
    public function process(SplFileInfo $file, array $data, array $options = []);

    public function getHandledExtensions(): array;
}
