<?php namespace Sereno;

use Sereno\Contracts\Extension;

abstract class AbstractExtension implements Extension
{
    public function boot(Application $app)
    {
        if (method_exists($this, 'provide')) {
            $this->provide();
        }

        $app->registerBuilders($this->getBuilders());
        $app->registerExtractors($this->getExtractors());
        $app->registerProcessors($this->getProcessors());
        $app->registerViewsDirectory($this->getViewsDirectory());
        $app->registerContentDirectory($this->getContentDirectory());
    }

    protected function registerConfig(string $prefix, array $config)
    {
        config()->set($prefix, $this->merge($config, (array) config($prefix, [])));
    }

    private function merge(array &$array1, array $array2)
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->merge($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    public function getBuilders(): array
    {
        return [];
    }

    public function getExtractors(): array
    {
        return [];
    }

    public function getProcessors(): array
    {
        return [];
    }

    public function getViewsDirectory(): array
    {
        return [];
    }

    public function getContentDirectory(): array
    {
        return [];
    }
}
