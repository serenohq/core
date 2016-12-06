<?php

namespace Sereno\Processors;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Symfony\Component\Finder\SplFileInfo;
use Sereno\Blade;
use Sereno\Parsers\FrontParser;
use Sereno\Parsers\Markdown;

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
        $this->engine = new PhpEngine();
        $this->frontParser = $frontParser;
    }

    public function process(SplFileInfo $file, array $data, array $options = [])
    {
        $path = $this->getPath($file);
        $filename = $this->getOutputFilename($file, array_get($options, 'interceptor'));
        app()->line('Markdown: '.$file->getRelativePathname().' -> '.$filename);

        $data['currentViewPath'] = $path;
        $data['currentUrlPath'] = $this->getUrl($filename);

        $content = $this->getContent($file, $data, $options);

        $this->writeContent($filename, $content);

        return $this;
    }

    protected function getContent(SplFileInfo $file, array $data, array $options): string
    {
        $this->frontParser->parse($file->getContents());
        $markdown = $this->frontParser->getMainContent();
        $viewData = $this->frontParser->getFrontContent();
        $viewContent = Markdown::parse((string) $markdown);
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

        return "@extends('${extends}')\r\n${sections}\r\n@section('${yields}')${viewContent}@stop";
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
