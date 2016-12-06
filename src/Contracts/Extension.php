<?php namespace Sereno\Contracts;

interface Extension
{
    public function getBuilders(): array;

    public function getExtractors(): array;

    public function getProcessors(): array;

    public function getViewsDirectory(): array;
}
