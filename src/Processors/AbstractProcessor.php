<?php

namespace Sereno\Processors;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Sereno\Contracts\Processor;
use Symfony\Component\Finder\SplFileInfo;

abstract class AbstractProcessor implements Processor
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    protected $indexNames = ['index'];

    protected $outputIndexName = 'index.html';

    protected $allowedExtensions = [];

    protected $specialFiles = [];

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->specialFiles = config('sereno.special_files');
        $this->sortExtensions();
    }

    protected function sortExtensions()
    {
        $this->allowedExtensions = array_sort($this->allowedExtensions, function ($ext) {
            return -strlen($ext);
        });
    }

    public function getHandledExtensions(): array
    {
        return $this->allowedExtensions;
    }

    protected function getOutputFilename(SplFileInfo $file, callable $interceptor = null): string
    {
        if (is_callable($interceptor)) {
            return call_user_func($interceptor, $file);
        }

        $extension = $this->getOutputFileExtension($file);

        if (is_null($extension)) {
            return $file->getRelativePathname();
        }

        $basename = str_replace_last($extension, '', $file->getFilename());

        if (in_array($basename, $this->specialFiles)) {
            return $file->getRelativePath().DIRECTORY_SEPARATOR.$basename.'.html';
        }

        if (in_array($basename, $this->indexNames)) {
            return $file->getRelativePath().DIRECTORY_SEPARATOR.$this->outputIndexName;
        }

        return $file->getRelativePath().DIRECTORY_SEPARATOR.$basename.DIRECTORY_SEPARATOR.$this->outputIndexName;
    }

    protected function getPath(SplFileInfo $file): string
    {
        $extension = $this->getOutputFileExtension($file);

        if (is_null($extension)) {
            throw new InvalidArgumentException('Expected blade file, found '.$file->getRelativePath());
        }

        return str_replace_last($extension, '', $file->getRelativePathname());
    }

    protected function getOutputFileExtension(SplFileInfo $file)
    {
        $filename = $file->getBasename();

        foreach ($this->allowedExtensions as $extension) {
            if (Str::endsWith($filename, $extension)) {
                return $extension;
            }
        }
    }

    protected function getUrl(string $filename): string
    {
        $filename = str_replace(public_dir(), '', $filename);
        $filename = str_replace_last($this->outputIndexName, '', $filename);
        $filename = str_replace('\\', '/', $filename);

        return $filename;
    }

    protected function writeContent(string $filename, $content)
    {
        $filename = $this->normalizeFilename($filename);
        $this->prepareOutputFiles($filename);
        $this->filesystem->put($filename, $content);
    }

    protected function normalizeFilename(string $filename): string
    {
        if (starts_with(public_dir(), $filename)) {
            return $filename;
        }

        return public_dir($filename);
    }

    protected function prepareOutputFiles(string $filename)
    {
        $directory = dirname($filename);

        if (! $this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
    }
}
