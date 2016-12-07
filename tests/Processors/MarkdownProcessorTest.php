<?php

use Sereno\Processors\MarkdownProcessor;

class MarkdownProcessorTest extends TestCase
{
    public function test_it_processes_markdown()
    {
        $processor = $this->getProcessor();

        $content = '# Heading {{ $name }}';
        $expected = '%A<h1>Heading 1</h1>%A';

        $processed = $processor->processString($content, ['name' => '1', 'currentUrlPath' => '/foo']);

        $this->assertStringMatchesFormat($expected, $processed);
    }

    public function test_variable_in_url()
    {
        $processor = $this->getProcessor();

        $content = '# Heading [{{ $name }}](/{{ $name }}/go)';
        $expected = '%A<h1>Heading <a href="/foo/go">foo</a></h1>%A';

        $processed = $processor->processString($content, ['name' => 'foo', 'currentUrlPath' => '/foo']);

        $this->assertStringMatchesFormat($expected, $processed);
    }

    public function test_directive()
    {
        $processor = $this->getProcessor();

        $content = "# Heading [{{ \$name }}](/{{ \$name }}/go){.@active('/'.\$name.'/go')}";
        $expected = '%A<h1>Heading <a class="active" href="/foo/go">foo</a></h1>%A';

        $processed = $processor->processString($content, ['name' => 'foo', 'currentUrlPath' => '/foo/go']);

        $this->assertStringMatchesFormat($expected, $processed);
    }

    protected function getProcessor(): MarkdownProcessor
    {
        return $this->app(MarkdownProcessor::class);
    }
}
