<?php

namespace Sereno\Traits;

trait RegisterExtensionsTrait {
    protected $processors = [];

    public function registerProcessors(array $processors)
    {
        $this->processors = array_merge($this->processors, $processors);
    }

    public function getProcessors(): array
    {
        return $this->processors;
    }

    protected $extractors = [];

    public function registerExtractors(array $extractors)
    {
        $this->extractors = array_merge($this->extractors, $extractors);
    }

    public function getExtractors(): array
    {
        return $this->extractors;
    }

    protected $builders = [];

    public function registerBuilders(array $builders)
    {
        $this->builders = array_merge($this->builders, $builders);
    }

    public function getBuilders(): array
    {
        return $this->builders;
    }

    protected $viewsDirectories = [];

    public function registerViewsDirectory(array $dirs)
    {
        foreach ($dirs as $dir) {
            array_unshift($this->viewsDirectories, $dir);
        }
    }

    public function getViewsDirectory(): array
    {
        return $this->viewsDirectories;
    }

    protected $contentDirectories = [];

    public function registerContentDirectory(array $dirs)
    {
        $this->contentDirectories = array_merge($this->contentDirectories, $dirs);
    }

    public function getContentDirectory(): array
    {
        return $this->contentDirectories;
    }
}
