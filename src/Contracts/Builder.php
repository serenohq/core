<?php

namespace Znck\Sereno\Contracts;

interface Builder
{
    public function handledPatterns(): array;

    public function data(array $files, array $data) : array;

    public function build(array $files, array $data);
}
