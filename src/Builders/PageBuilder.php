<?php

namespace Znck\Sereno\Builders;

use Znck\Sereno\Contracts\Builder;
use Znck\Sereno\ProcessorFactory;
use Znck\Sereno\SiteGenerator;

class PageBuilder implements Builder
{
    /**
     * @var \Znck\Sereno\ProcessorFactory
     */
    protected $factory;

    public function __construct(ProcessorFactory $factory)
    {
        $this->factory = $factory;
    }

    public function handledPatterns(): array
    {
        return [SiteGenerator::DEFAULT_BUILDER];
    }

    public function data(array $files, array $data) : array
    {
        return $data + array_except(config()->all(), ['sereno', 'view', 'blog', 'docs']);
    }

    public function build(array $files, array $data)
    {
        foreach ($files as $file) {
            $this->factory->process($file, $data);
        }
    }
}
