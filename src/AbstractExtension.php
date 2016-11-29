<?php namespace Znck\Sereno;

use Znck\Sereno\Contracts\Extension;

abstract class AbstractExtension implements Extension
{
    public function getBuilders(): array
    {
        return [];
    }

    public function getExtractors(): array
    {
        return [];
    }

    public function getProcessors(): array
    {
        return [];
    }

    public function getViewsDirectory(): array
    {
        return [];
    }
}
