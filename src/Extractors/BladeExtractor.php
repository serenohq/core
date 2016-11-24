<?php

namespace Znck\Sereno\Extractors;

use Illuminate\View\Factory;
use Illuminate\View\View;
use Symfony\Component\Finder\SplFileInfo;
use Znck\Sereno\Contracts\Extractor;

class BladeExtractor implements Extractor
{
    protected $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function handles(): array
    {
        return ['.blade.php'];
    }

    public function get(SplFileInfo $file)
    {
        $path = preg_replace('/\.blade\.php$/', '', $file->getRelativePathname());
        $view = $this->getView($path);
        $data = [];

        $view->render(function (View $view) use (&$data) {
            $data = $view->getFactory()->getSections();
        });

        return $data + ['__extension' => '.blade.php'];
    }

    protected function getView($path): View
    {
        return $this->factory->make($path);
    }
}
