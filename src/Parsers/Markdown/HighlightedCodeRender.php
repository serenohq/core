<?php

namespace Sereno\Parsers\Markdown;

use Highlight\Highlighter;
use InvalidArgumentException;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Element\IndentedCode;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;

class HighlightedCodeRender implements BlockRendererInterface
{
    /**
     * @param AbstractBlock            $block
     * @param ElementRendererInterface $htmlRenderer
     * @param bool                     $inTightList
     *
     * @return HtmlElement|string
     */
    public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, $inTightList = false)
    {
        if (! ($block instanceof FencedCode or $block instanceof IndentedCode)) {
            throw new InvalidArgumentException('Incompatible block type: '.get_class($block));
        }

        $attrs = [];
        foreach ($block->getData('attributes', []) as $key => $value) {
            $attrs[$key] = $htmlRenderer->escape($value, true);
        }

        $highlighter = $this->getHighlighter();
        $infoWords = $block instanceof FencedCode ? $block->getInfoWords() : [];
        if (count($infoWords) !== 0 && strlen($infoWords[0]) !== 0) {
            $attrs['class'] = isset($attrs['class']) ? $attrs['class'].' ' : '';
            $attrs['class'] .= 'language-'.$htmlRenderer->escape($infoWords[0], true);
            $code = $highlighter->highlight($this->normalizeLang($infoWords[0]), $block->getStringContent());
        } else {
            $code = (object) [
                'value' => $htmlRenderer->escape($block->getStringContent()),
            ];
        }

        return new HtmlElement(
            'pre', [], new HtmlElement('code', $attrs, $this->lines($code->value))
        );
    }

    /**
     * @return Highlighter
     */
    protected function getHighlighter(): Highlighter
    {
        $highlighter = new Highlighter();

        return $highlighter;
    }

    protected function normalizeLang($lang)
    {
        switch ($lang) {
            case 'js':
                return 'javascript';
            case 'sass':
                return 'scss';
            default:
                return strtolower($lang);
        }
    }

    public function lines(string $code)
    {
        return implode(PHP_EOL, array_map(function ($line) {
            return '<span class="line">'.$line.'</span>';
        }, explode(PHP_EOL, preg_replace('/\r?\n$/', '', $code))));
    }
}
