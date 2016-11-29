<?php namespace Znck\Sereno;

class DefaultExtension extends AbstractExtension
{
    public function getBuilders(): array
    {
        return [
            \Znck\Sereno\Builders\BlogPostBuilder::class,
            \Znck\Sereno\Builders\CollectionBuilder::class,
            \Znck\Sereno\Builders\BlogBuilder::class,
            \Znck\Sereno\Builders\DocsBuilder::class,
            \Znck\Sereno\Builders\PageBuilder::class,
        ];
    }

    public function getExtractors(): array
    {
        return [
            \Znck\Sereno\Extractors\BladeExtractor::class,
            \Znck\Sereno\Extractors\MarkdownYamlExtractor::class,
        ];
    }

    public function getProcessors(): array
    {
        return [
            \Znck\Sereno\Processors\FileProcessor::class,
            \Znck\Sereno\Processors\BladeProcessor::class,
            \Znck\Sereno\Processors\MarkdownProcessor::class,
        ];
    }

    public function getViewsDirectory(): array {
        return [__DIR__.'/../resources/views/blog/index.blade.php'];
    }
}
