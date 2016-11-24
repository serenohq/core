<?php

namespace Znck\Sereno\Parsers;

use Symfony\Component\Yaml\Yaml;

class FrontParser
{
    protected $frontContent;

    protected $mainContent;

    protected $startSep = ['---'];
    protected $endSep = ['---'];

    public function parse(string $text) : self
    {
        $quote = function ($str) {
            return preg_quote($str, '~');
        };
        $regex = '~^('
                 .implode('|', array_map($quote, $this->startSep)) // $matches[1] start separator
                 ."){1}[\r\n|\n]*(.*?)[\r\n|\n]+("                       // $matches[2] between separators
                 .implode('|', array_map($quote, $this->endSep))   // $matches[3] end separator
                 ."){1}[\r\n|\n]*(.*)$~s";                               // $matches[4] document content

        if (preg_match($regex, $text, $matches) === 1) {
            $this->frontContent = (array) (trim($matches[2]) !== '' ? Yaml::parse(trim($matches[2])) : []);
            $this->mainContent = ltrim($matches[4]);
        } else {
            $this->frontContent = [];
            $this->mainContent = ltrim($text);
        }

        return $this;
    }

    public function getFrontContent() : array
    {
        return $this->frontContent;
    }

    public function getMainContent() : string
    {
        return $this->mainContent;
    }
}
