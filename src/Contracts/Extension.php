<?php namespace Sereno\Contracts;
use Sereno\Application;

interface Extension
{
    public function boot(Application $app);

    public function getBuilders(): array;

    public function getExtractors(): array;

    public function getProcessors(): array;

    public function getViewsDirectory(): array;

    public function getContentDirectory(): array;
}
