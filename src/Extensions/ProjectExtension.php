<?php namespace Sereno\Extensions;

use Sereno\AbstractExtension;

class ProjectExtension extends AbstractExtension
{
    public function getBuilders(): array
    {
        return [
            \Sereno\Builders\ProjectBuilder::class,
        ];
    }
}
