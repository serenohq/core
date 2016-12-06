<?php

namespace Test\Lex\Parsers;

use Sereno\Parsers\FrontParser;

class FrontParserTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_parses_correct_yml()
    {
        $input = <<<'EOF'
---
foo: bar
other: value
items:
    - one
    - two
    - three
---

# Hello World

This is it.
EOF;
        $parser = (new FrontParser())->parse($input);

        $this->assertNotEmpty($parser->getFrontContent());
        $this->assertArrayHasKey('foo', $parser->getFrontContent());
        $this->assertStringStartsWith('# Hello', $parser->getMainContent());
    }

    public function test_it_parses_file_without_yml()
    {
        $input = <<<'EOF'
# Hello World

This is it.
EOF;
        $parser = (new FrontParser())->parse($input);

        $this->assertEmpty($parser->getFrontContent());
        $this->assertStringStartsWith('# Hello', $parser->getMainContent());
    }
}
