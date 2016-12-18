<?php namespace Sereno\Builders;

use Sereno\Contracts\Builder;
use Sereno\DataExtractor;
use Sereno\Parsers\FrontParser;
use Sereno\ProcessorFactory;
use Symfony\Component\Finder\SplFileInfo;

class ProjectBuilder implements Builder
{
    use \Sereno\Traits\ViewFinderTrait;
    /**
     * List of projects.
     *
     * @var array
     */
    protected $projects = [];

    /**
     * Project directory.
     *
     * @var string
     */
    protected $projectDirectory;

    /**
     * Project URL prefix.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Extracts front data.
     *
     * @var \Sereno\DataExtractor
     */
    protected $extractor;

    protected $markdown;

    /**
     * Converts to HTML page.
     *
     * @var \Sereno\ProcessorFactory
     */
    protected $factory;

    public function __construct(DataExtractor $extractor, ProcessorFactory $factory, FrontParser $markdown)
    {
        $this->extractor = $extractor;
        $this->factory = $factory;
        $this->markdown = $markdown;
        $this->projectDirectory = config('project.directory');
        $this->baseUrl = config('project.url_prefix');
    }

    public function handledPatterns(): array
    {
        return [$this->projectDirectory.'/*'];
    }

    public function data(array $files, array $data) : array
    {
        $projects = $this->findProjects($files);

        return array_set($data, 'projects', $projects);
    }

    public function build(array $files, array $data)
    {
        $this->restoreProjectsFromData($data);
        $options = [
            'view' => [
                'extends' => config('project.extends'),
                'yields'  => config('project.yields'),
            ],
            'interceptor' => [$this, 'getOutputFilename'],
        ];

        $file = $this->getView('project.index');

        $this->factory->process($file, $data, $options);
    }

    public function getOutputFilename(SplFileInfo $file)
    {
        return rtrim($this->baseUrl, '/').DIRECTORY_SEPARATOR.'index.html';
    }

    /**
     * @param SplFileInfo[] $files
     *
     * @return array
     */
    protected function findProjects(array $files): array
    {
        foreach ($files as $file) {
            if ($data = $this->extractor->get($file)) {
                $this->projects[$file->getRelativePathname()] = $data;
                $this->projects[$file->getRelativePathname()] += [
                    'content'  => $this->markdown->parse($file->getContents())->getMainContent(),
                    'path'     => $this->getPostUrl($file),
                    'pathname' => $file->getRelativePathname(),
                    'title'    => ucfirst(str_replace('-', ' ', array_first(explode('.', $file->getFilename(), 2)))),
                ];

                $this->projects[$file->getRelativePathname()] = (object) $this->projects[$file->getRelativePathname()];
            }
        }

        return $this->projects;
    }

    protected function getPostUrl(SplFileInfo $file): string
    {
        $filename = $this->getOutputFilename($file);

        return str_replace('\\', '/', dirname($filename));
    }

    protected function restoreProjectsFromData(array $data)
    {
        $this->projects = array_combine(array_keys($this->projects), array_get($data, 'projects'));
    }
}
