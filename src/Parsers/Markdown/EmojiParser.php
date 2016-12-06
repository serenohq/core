<?php namespace Sereno\Parsers\Markdown;

use HeyUpdate\Emoji\Index\CompiledIndex;
use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Inline\Parser\AbstractInlineParser;
use League\CommonMark\InlineParserContext;

class EmojiParser extends AbstractInlineParser
{
    protected $emoji;

    public function __construct()
    {
        $this->emoji = new CompiledIndex();
    }

    public function getCharacters()
    {
        return [':'];
    }

    public function parse(InlineParserContext $inlineContext)
    {
        $cursor = $inlineContext->getCursor();
        $previous = $cursor->peek(-1);
        if ($previous !== null && $previous !== ' ') {
            return false;
        }
        $saved = $cursor->saveState();
        $cursor->advance();
        $handle = $cursor->match('/^[a-z0-9\+\-_]+:/');
        if (! $handle) {
            $cursor->restoreState($saved);

            return false;
        }
        $next = $cursor->peek(0);
        if ($next !== null && ! preg_match('/[^a-z0-9]/', $next)) {
            $cursor->restoreState($saved);

            return false;
        }
        $key = substr($handle, 0, -1);
        if (is_null($src = $this->emoji->findByName($key))) {
            $cursor->restoreState($saved);

            return false;
        }
        $src = $src['unicode'];
        $inline = new Image("//twemoji.maxcdn.com/2/72x72/${src}.png", $key);
        $inline->data['attributes'] = ['class' => 'emoji', 'alt' => ":${key}:"];
        $inlineContext->getContainer()->appendChild($inline);

        return true;
    }
}
