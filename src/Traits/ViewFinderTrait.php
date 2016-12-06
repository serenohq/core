<?php namespace Sereno\Traits;

use Illuminate\View\Factory;
use Symfony\Component\Finder\SplFileInfo;

trait ViewFinderTrait
{
    /**
     * Create instance of SplFileInfo.
     *
     * @param string $path
     *
     * @return SplFileInfo
     */
    protected function createSplFileInfoInstance(string $directory, string $filename): SplFileInfo
    {
        return new SplFileInfo($directory.DIRECTORY_SEPARATOR.$filename, $directory, $filename);
    }

    /**
     * Find view.
     *
     * @param string $name
     *
     * @return SplFileInfo
     */
    public function getView(string $name): SplFileInfo
    {
        $viewFactory = app(Factory::class);

        $pattern = str_replace('.', DIRECTORY_SEPARATOR, $name);
        $path = $viewFactory->getFinder()->find($name);

        $pos = strpos($path, $pattern);

        $directory = substr($path, 0, $pos);
        $filename = substr($path, $pos);

        return $this->createSplFileInfoInstance($directory, $filename);
    }
}
