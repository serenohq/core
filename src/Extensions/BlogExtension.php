<?php namespace Sereno\Extensions;

use Sereno\AbstractExtension;

class BlogExtension extends AbstractExtension
{
    public function getBuilders(): array
    {
        return [
            \Sereno\Builders\BlogPostBuilder::class,
            \Sereno\Builders\CollectionBuilder::class,
            \Sereno\Builders\BlogBuilder::class,
        ];
    }
}
