<?php

namespace Znck\Sereno;

use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Znck\Sereno\Contracts\Processor;

class ProcessorFactory
{
    protected $processors = [];

    protected $extensions = [];

    protected $defaultProcessor;

    public function registerDefaultProcessor(Processor $processor)
    {
        $this->defaultProcessor = $processor;
    }

    public function register(Processor $processor)
    {
        $extensions = $processor->getHandledExtensions();

        foreach ($extensions as $extension) {
            $this->processors[$extension] = $processor;
        }

        $this->addExtensions($extensions);
    }

    public function process(SplFileInfo $file, array $data, array $options = [])
    {
        $processor = $this->findProcessor($file);

        $processor->process($file, $data, $options);
    }

    protected function addExtensions(array $extensions)
    {
        $this->extensions = array_sort(array_merge($extensions, $this->extensions), function ($ext) {
            return -strlen($ext);
        });
    }

    protected function findProcessor(SplFileInfo $file): Processor
    {
        $filename = $file->getFilename();

        foreach ($this->extensions as $extension) {
            if (Str::endsWith($filename, $extension)) {
                return $this->processors[$extension];
            }
        }

        return $this->defaultProcessor;
    }
}
