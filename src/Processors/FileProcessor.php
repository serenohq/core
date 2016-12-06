<?php

namespace Sereno\Processors;

use Symfony\Component\Finder\SplFileInfo;

class FileProcessor extends AbstractProcessor
{
    protected $allowedExtensions = ['*'];

    public function process(SplFileInfo $file, array $data, array $options = [])
    {
        $filename = $this->getOutputFilename($file, array_get($options, 'interceptor'));

        app()->line('Copy: '.$file->getRelativePathname().' -> '.$filename);

        $this->writeContent($filename, $file->getContents());
    }
}
