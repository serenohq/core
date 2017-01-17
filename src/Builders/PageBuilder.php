<?php

namespace Sereno\Builders;

use Sereno\Contracts\Builder;
use Sereno\ProcessorFactory;
use Sereno\SiteGenerator;

class PageBuilder implements Builder
{
    /**
     * @var \Sereno\ProcessorFactory
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
        foreach (config()->all() as $key => $value) {
            if (! isset($data[$key])) {
                $data[$key] = config($key);
            }
        }

        return $data;
    }

    public function build(array $files, array $data)
    {
        foreach ($files as $file) {
            $this->factory->process($file, $data);
        }
    }
}
