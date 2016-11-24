<?php

namespace Znck\Sereno\Extractors;

use Symfony\Component\Finder\SplFileInfo;
use Znck\Sereno\Contracts\Extractor;
use Znck\Sereno\Parsers\FrontParser;

class MarkdownYamlExtractor implements Extractor
{
    /**
     * @var \Znck\Sereno\Parsers\FrontParser
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
