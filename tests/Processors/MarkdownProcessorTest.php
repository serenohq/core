<?php

use Sereno\Processors\MarkdownProcessor;
use Symfony\Component\Finder\SplFileInfo;

class MarkdownProcessorTest extends TestCase
{
    public function test_it_processes_markdown() {
        $processor = $this->getProcessor();

        $content = '# Heading {{ $name }}';
        $expected = '<h1>Heading 1</h1>';

        $processed = $processor->process($this->getFile($content), ['name' => '1'], []);

        $this->assertEquals($expected, $processed);
    }

    protected function getProcessor(): MarkdownProcessor {
        return $this->app(MarkdownProcessor::class);
    }
}
