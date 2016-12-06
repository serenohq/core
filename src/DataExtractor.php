<?php

namespace Sereno;

use Illuminate\Support\Str;
use Sereno\Contracts\Extractor;
use Symfony\Component\Finder\SplFileInfo;

class DataExtractor
{
    protected $extractors = [];

    protected $extensions = [];

    public function register(Extractor $extractor)
    {
        foreach ($extractor->handles() as $extension) {
            $this->extractors[$extension] = $extractor;
        }

        $this->extensions = array_sort(array_merge($this->extensions, $extractor->handles()), function (string $ext) {
            return -strlen($ext);
        });
    }

    public function get(SplFileInfo $file)
    {
        foreach ($this->extensions as $extension) {
            if (Str::endsWith($file->getFilename(), $extension)) {
                return $this->extractors[$extension]->get($file);
            }
        }
    }
}
