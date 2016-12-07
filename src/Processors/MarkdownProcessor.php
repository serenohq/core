<?php

namespace Sereno\Processors;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Sereno\Blade;
use Sereno\Parsers\FrontParser;
use Symfony\Component\Finder\SplFileInfo;

class MarkdownProcessor extends AbstractProcessor
{
    protected $allowedExtensions = ['.md'];

    protected $engine;
    protected $frontParser;
    protected $viewFactory;
    protected $markConverter;

    public function __construct(Filesystem $filesystem, Factory $viewFactory, FrontParser $frontParser)
    {
        parent::__construct($filesystem);

        $this->viewFactory = $viewFactory;
        $this->frontParser = $frontParser;
        $this->engine = new PhpEngine();
    }

    public function process(SplFileInfo $file, array $data, array $options = [])
    {
        $filename = $this->getOutputFilename($file, array_get($options, 'interceptor'));

        $data['currentViewPath'] = $this->getPath($file);
        $data['currentUrlPath'] = $this->getUrl($filename);

        debug('Markdown: '.$file->getRelativePathname().' -> '.$filename);

        $content = $this->getContent($file, $data, $options);

        $this->writeContent($filename, $content);

        return $this;
    }

    protected function getContent(SplFileInfo $file, array $data, array $options): string
    {
        $cache = $this->getCacheFile($file);

        if ($this->isExpired($cache, $file)) {
            $this->filesystem->delete($cache);
        }

        return $this->processString($file->getContents(), $data, $options, $cache);
    }

    protected function getCacheFile($filename): string
    {
        if ($filename instanceof SplFileInfo) {
            $filename = $filename->getRelativePathname();
        }

        return cache_dir(sha1($filename).'.php');
    }

    protected function isExpired(string $cache, SplFileInfo $file): bool
    {
        if (! $this->filesystem->exists($cache)) {
            return true;
        }

        $lastModified = $this->filesystem->lastModified($file->getPathname());

        return $lastModified >= $this->filesystem->lastModified($cache);
    }

    public function processString(string $content, array $data, array $options = [], string $cache = null)
    {
        $this->frontParser->parse($content);

        $markdown = $this->frontParser->getMainContent();
        $viewData = $this->frontParser->getFrontContent();
        $viewSource = $this->buildView($markdown, $viewData + $data, $options);

        $removeCache = false;
        if (is_null($cache)) {
            $removeCache = true;
            $cache = $this->getCacheFile(str_random());
        }

        $result = $this->compile($viewSource, $viewData + $data, $cache);

        if ($removeCache) {
            $this->filesystem->delete($cache);
        }

        return $result;
    }

    protected function buildView(string $viewContent, array $data, array $options): string
    {
        $extends = array_get($data, 'view.extends') ??
                   array_get($data, 'view::extends') ??
                   array_get($options, 'view.extends') ??
                   config('view.extends');

        $yields = array_get($data, 'view.yields') ??
                  array_get($data, 'view::yields') ??
                  array_get($options, 'view.yields') ??
                  config('view.yields');

        return "@extends('${extends}')\n@section('${yields}')\n".
               "@markdown\n${viewContent}\n@endmarkdown\n@stop";
    }

    protected function compile(string $view, array $data, string $cache): string
    {
        if (! $this->filesystem->exists($cache)) {
            $this->filesystem->put($cache, $content = $this->getCompiler()->compileString($view));
        }

        return $this->engine->get($cache, $this->getViewData($data));
    }

    protected function getCompiler(): BladeCompiler
    {
        /** @var Blade $blade */
        $blade = $this->viewFactory->getEngineResolver()->resolve('blade');

        return $blade->getCompiler();
    }

    protected function getViewData($data)
    {
        $data = array_merge($this->viewFactory->getShared(), $data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }
}
