<?php namespace Sereno\Extensions;

use Sereno\AbstractExtension;

class DefaultExtension extends AbstractExtension
{
    public function getBuilders(): array
    {
        return [
            \Sereno\Builders\PageBuilder::class,
        ];
    }

    public function getExtractors(): array
    {
        return [
            \Sereno\Extractors\BladeExtractor::class,
            \Sereno\Extractors\MarkdownYamlExtractor::class,
        ];
    }

    public function getProcessors(): array
    {
        return [
            \Sereno\Processors\FileProcessor::class,
            \Sereno\Processors\BladeProcessor::class,
            \Sereno\Processors\MarkdownProcessor::class,
        ];
    }
}
