<?php

namespace Sereno\Extractors;

use Sereno\Contracts\Extractor;
use Sereno\Parsers\FrontParser;
use Symfony\Component\Finder\SplFileInfo;

class MarkdownYamlExtractor implements Extractor
{
    /**
     * @var \Sereno\Parsers\FrontParser
     */
    protected $parser;

    public function __construct(FrontParser $parser)
    {
        $this->parser = $parser;
    }

    public function handles(): array
    {
        return ['.md'];
    }

    public function get(SplFileInfo $file)
    {
        $data = $this->parser->parse($file->getContents())->getFrontContent();

        return $data + ['__extension' => '.md'];
    }
}
