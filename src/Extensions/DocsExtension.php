<?php namespace Sereno\Extensions;

use Sereno\AbstractExtension;

class DocsExtension extends AbstractExtension
{
    public function getBuilders(): array
    {
        return [
            \Sereno\Builders\DocsBuilder::class,
        ];
    }
}
