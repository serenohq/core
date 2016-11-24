<?php namespace Znck\Sereno\Builders;

use Znck\Sereno\Contracts\Builder;
use Znck\Sereno\DataExtractor;
use Znck\Sereno\ProcessorFactory;
use Znck\Sereno\Parsers\Markdown;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\Contracts\Support\Renderable;

class DocsBuilder implements Builder
{
    /**
     * File processor generates HTML from source.
     *
     * @var \Znck\Sereno\ProcessorFactory
     */
    protected $processor;

    /**
     * Data Extractor parses front data from a file.
     *
     * @var \Znck\Sereno\DataExtractor
     */
    protected $extractor;

    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystems\Filesystem
     */
    protected $filesystem;

    /**
     * Blade view factory.
     *
     * @var \Illuminate\View\Factory
     */
    protected $viewFactory;

    /**
     * The directory containing docs.
     *
     * @var string
     */
    protected $docsDirectory;

    /**
     * The filename to choose as index.
     *
     * @var string
     */
    protected $indexFilename;

    /**
     * The base URL for docs.
     *
     * @var string
     */
    protected $baseURL;

    public function __construct(ProcessorFactory $processor, DataExtractor $extractor, Filesystem $filesystem, Factory $viewFactory)
    {
        $this->processor = $processor;
        $this->extractor = $extractor;
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;

        $this->docsDirectory = config('docs.directory');
        $this->indexFilename = config('docs.index');
        $this->baseURL = config('docs.url_prefix');
    }

    public function handledPatterns(): array {
        return [$this->docsDirectory.'/*'];
    }

    public function data(array $files, array $data): array {
        return $data;
    }

    public function build(array $files, array $data) {
        list($index, $docs) = $this->filterFiles($files);
        $options = [
            'view' => [
                'extends' => config('docs.extends'),
                'yields' => config('docs.yields'),
            ],
            'interceptor' => [$this, 'getOutputFilename'],
        ];
        $index = $this->compileWithBlade($index, $data);

        foreach ($docs as $doc) {
            $this->processor->process($doc, $data + compact('index'), $options);
        }

        $first = array_first($docs);
        $path = config('docs.default', array_first(explode('.', $first->getBasename(), 2)));
        $this->processor->process($this->getRedirectFile(), ['target' => url($this->baseURL).'/'.$path] + $data,
                ['interceptor' => function () { return $this->baseURL.DIRECTORY_SEPARATOR.'index.html'; }]);
    }

    protected function getRedirectFile() {
        $filename = __DIR__.'/../resources/views/_includes/redirect.blade.php';

        return new SplFileInfo($filename, '_includes', '_includes/redirect.blade.php');
    }

    protected function compileWithBlade(string $content, array $data): string {
        $viewCache = cache_dir(sha1($this->indexFilename).'.php');
        $this->filesystem->put($viewCache, $this->getCompiler()->compileString("${content}"));
        $content = (new PhpEngine)->get($viewCache, $this->getViewData(['docs_url' => rtrim(url($this->baseURL), '/')] + $data));
        return Markdown::parse($content);
    }

    public function getOutputFilename(SplFileInfo $file): string {
        $filename = $file->getFilename();
        $extension = last(explode('.', $filename, 2));
        $basename = preg_replace('/\.'.preg_quote($extension).'$/', '', $filename);
        $directory = preg_replace('#^'.preg_quote($this->docsDirectory, '#').'#', '', $file->getRelativePath());
        $baseURL = str_replace('/', DIRECTORY_SEPARATOR, $this->baseURL);
        $directory = trim(trim($baseURL, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.trim($directory, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

        if (hash_equals('', $directory)) {
            return $basename.DIRECTORY_SEPARATOR.'index.html';
        }

        return $directory.DIRECTORY_SEPARATOR.$basename.DIRECTORY_SEPARATOR.'index.html';
    }

    protected function getOutputUrl(SplFileInfo $file): string {
        return str_replace('\\', '/', dirname($this->getOutputFilename($file)));
    }

    protected function filterFiles(array $files): array {
        $index = null;
        $docs = array_filter($files, function (SplFileInfo $file) use (&$index) {
            if (starts_with($file->getBasename(), $this->indexFilename)) {
                $index = $file;

                return false;
            }

            return true;
        });

        if (is_null($index)) {
            $index = $this->buildIndex($docs);
        } else {
            $index = $index->getContents();
        }

        return [$index, $docs];
    }

    protected function buildIndex(array $files): string {
        $items = array_map(function (SplFileInfo $file) {
            $url = $this->getOutputUrl($file);
            $title = ucfirst(str_replace('-', ' ', last(explode('/', $url))));

            return compact('url', 'title');
        }, $files);

        return array_reduce($items, function (string $content, array $item) {
            return $content .= "- [{$item['title']}]({$item['url']})\n";
        }, '');
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

    protected function getCompiler(): BladeCompiler
    {
        /** @var Blade $blade */
        $blade = $this->viewFactory->getEngineResolver()->resolve('blade');

        return $blade->getCompiler();
    }
}
