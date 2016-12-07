<?php

namespace Sereno\Processors;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Sereno\Blade;
use Sereno\Parsers\FrontParser;
use Sereno\Parsers\Markdown;
use Symfony\Component\Finder\SplFileInfo;

class MarkdownProcessor extends AbstractProcessor
{
    protected $allowedExtensions = ['.md'];

    protected $engine;
    protected $frontParser;
    protected $viewFactory;
    protected $markConverter;

    protected $currentFile;

    public function __construct(Filesystem $filesystem, Factory $viewFactory, FrontParser $frontParser)
    {
        parent::__construct($filesystem);

        $this->viewFactory = $viewFactory;
        $this->frontParser = $frontParser;
        $this->engine = new PhpEngine();
    }

    public function process(SplFileInfo $file, array $data, array $options = [])
    {
        $this->currentFile = $file;
        $path = $this->getPath($file);
        $filename = $this->getOutputFilename($file, array_get($options, 'interceptor'));
        debug('Markdown: '.$file->getRelativePathname().' -> '.$filename);

        $data['currentViewPath'] = $path;
        $data['currentUrlPath'] = $this->getUrl($filename);

        $content = $this->getContent($file, $data, $options);

        $this->writeContent($filename, $content);

        return $this;
    }

    protected function getContent(SplFileInfo $file, array $data, array $options): string
    {
        $this->frontParser->parse($file->getContents());
        $viewContent = (string) $this->frontParser->getMainContent();
        $viewData = $this->frontParser->getFrontContent();
        $viewCache = $this->getCacheFile($file);

        $view = $this->buildView($viewContent, $viewData + $data, $options);

        if ($this->isExpired($viewCache, $file)) {
            $this->filesystem->put($viewCache, $this->getCompiler()->compileString($view));
        }

        return $this->engine->get($viewCache, $this->getViewData($viewData + $data));
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

    protected function buildView(string $viewContent, array $data, array $options): string
    {
        $sections = '';
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sections .= "@section('${key}', '".addslashes($value)."')\r\n";
            }
        }

        $extends = array_get($data, 'view.extends') ??
                   array_get($data, 'view::extends') ??
                   array_get($options, 'view.extends') ??
                   config('view.extends');

        $yields = array_get($data, 'view.yields') ??
                  array_get($data, 'view::yields') ??
                  array_get($options, 'view.yields') ??
                  config('view.yields');

        return "@extends('${extends}')\n${sections}\n@section('${yields}')\n@markdown\n${viewContent}\n@endmarkdown\n@stop";
    }

    protected function getCacheFile(SplFileInfo $file): string
    {
        return cache_dir(sha1($file->getRelativePathname()).'.php');
    }

    protected function isExpired(string $cache, SplFileInfo $file): bool
    {
        if (! $this->filesystem->exists($cache)) {
            return true;
        }

        $lastModified = $this->filesystem->lastModified($file->getPathname());

        return $lastModified >= $this->filesystem->lastModified($cache);
    }

    protected function getCompiler(): BladeCompiler
    {
        /** @var Blade $blade */
        $blade = $this->viewFactory->getEngineResolver()->resolve('blade');

        return $blade->getCompiler();
    }
}
