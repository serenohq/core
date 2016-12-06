<?php

namespace Sereno\Parsers;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extras\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extras\TwitterHandleAutolink\TwitterHandleAutolinkExtension;
use Sereno\Parsers\Markdown\EmojiParser;
use Sereno\Parsers\Markdown\HighlightedCodeRender;
use Webuni\CommonMark\AttributesExtension\AttributesExtension;
use Webuni\CommonMark\TableExtension\TableExtension;

class Markdown
{
    protected static $converter;

    public static function parse(string $text) : string
    {
        return self::converter()->convertToHtml(self::cleanLeadingSpace($text));
    }

    private static function cleanLeadingSpace($text)
    {
        $lines = explode("\n", $text);
        $firstLine = array_first($lines);

        foreach ($lines as $value) {
            if (strlen($value) > 0) {
                $firstLine = $value;
                break;
            }
        }

        preg_match('/^( *)/', $firstLine, $matches);

        return preg_replace('/^[ ]{'.strlen($matches[1]).'}/m', '', $text);
    }

    /**
     * @return CommonMarkConverter
     */
    protected static function converter(): CommonMarkConverter
    {
        if (! self::$converter) {
            $environment = Environment::createCommonMarkEnvironment();

            $environment->addExtension(new SmartPunctExtension());
            $environment->addExtension(new TwitterHandleAutolinkExtension());
            $environment->addExtension(new TableExtension());
            $environment->addExtension(new AttributesExtension());

            $codeRenderer = new HighlightedCodeRender();
            $environment->addBlockRenderer('League\CommonMark\Block\Element\FencedCode', $codeRenderer);
            $environment->addBlockRenderer('League\CommonMark\Block\Element\IndentedCode', $codeRenderer);

            $environment->addInlineParser(new EmojiParser());

            $config = [];

            self::$converter = new CommonMarkConverter($config, $environment);
        }

        return self::$converter;
    }
}
