<?php

namespace Znck\Sereno;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use Symfony\Component\Finder\SplFileInfo;
use Znck\Sereno\Contracts\Builder;

class SiteGenerator
{
    const DEFAULT_BUILDER = '__default';
    protected $app;
    protected $filesystem;
    protected $viewFactory;
    protected $builders = [];
    protected $resourcesDirectory = '_includes';

    public function __construct(Application $app, Filesystem $filesystem, Factory $viewFactory)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
    }

    public function register(Builder $builder)
    {
        foreach ($builder->handledPatterns() as $pattern) {
            if (array_key_exists($pattern, $this->builders)) {
                $this->builders[$pattern][] = $builder;
            } else {
                $this->builders[$pattern] = [$builder];
            }
        }
    }

    public function build()
    {
        $groups = $this->groupFiles($this->getAllFiles());
        $data = $this->buildData($groups);

        $this->prepareOutputDirectory();
        $this->callBuilders($groups, $data);
    }

    protected function groupFiles(array $files) : array
    {
        $groups = [
            self::DEFAULT_BUILDER => [],
        ];

        foreach ($files as $file) {
            $name = $this->findBuilder($file);
            if (is_null($name)) {
                $groups[self::DEFAULT_BUILDER][] = $file;
            } else {
                if (!array_key_exists($name, $groups)) {
                    $groups[$name] = [];
                }

                $groups[$name][] = $file;
            }
        }

        return $groups;
    }

    protected function getAllFiles() : array
    {
        $files = array_filter(
            $this->filesystem->allFiles(content_dir()),
            function (SplFileInfo $file) {
                return !Str::startsWith($file->getRelativePathname(), $this->resourcesDirectory);
            }
        );

        return $files;
    }

    protected function buildData(array $groups) : array
    {
        $data = [];

        foreach ($groups as $name => $files) {
            foreach ($this->builders[$name] as $builder) {
                /** @var Builder $builder */
                $data = $builder->data($files, $data);
            }
        }

        return $data;
    }

    protected function callBuilders($groups, $data)
    {
        foreach ($groups as $name => $files) {
            foreach ($this->builders[$name] as $builder) {
                app()->line('=> <info>Build: '.$name.', Use: '.get_class($builder).'</info>');
                /* @var Builder $builder */
                $builder->build($files, $data);
            }
        }
    }

    protected function findBuilder(SplFileInfo $file)
    {
        foreach ($this->builders as $pattern => $builder) {
            if (starts_with($pattern, '/') and preg_match($pattern, $file->getRelativePathname())) {
                return $pattern;
            }

            if (str_is($pattern, $file->getRelativePathname()) or str_is($pattern, $file->getRelativePath())) {
                return $pattern;
            }
        }

        return null;
    }

    public function getResourcesDirectory(): string
    {
        return $this->resourcesDirectory;
    }

    public function setResourcesDirectory(string $resourcesDirectory)
    {
        $this->resourcesDirectory = $resourcesDirectory;
    }

    protected function prepareOutputDirectory()
    {
        if (!$this->filesystem->exists(public_dir())) {
            $this->filesystem->makeDirectory(public_dir(), 0755, true);
        }

        $this->filesystem->cleanDirectory(public_dir());
    }
}
