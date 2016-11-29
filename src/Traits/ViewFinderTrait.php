<?php namespace Znck\Sereno\Traits;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\View\Factory;
use Symfony\Component\Finder\SplFileInfo;

trait ViewFinderTrait {

    /**
     * Create instance of SplFileInfo
     *
     * @param  string $path
     *
     * @return SplFileInfo
     */
    protected function createSplFileInfoInstance(string $filename): SplFileInfo {
        $directory = dirname($filename);
        $filename = basename($filename);
        return new SplFileInfo($directory.DIRECTORY_SEPARATOR.$filename, '', $filename);
    }

    /**
     * Find view
     *
     * @param  string $name
     *
     * @return SplFileInfo
     */
    public function getView(string $name): SplFileInfo {
        $viewFactory = app(Factory::class);

        return $this->createSplFileInfoInstance($viewFactory->getFInder()->find($name));
    }
}
