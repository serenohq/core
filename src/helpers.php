<?php

use Znck\Sereno\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;

if (!function_exists('app')) {
    /**
     * @param null|string $type
     *
     * @return Application
     */
    function app($type = null)
    {
        if (is_null($type)) {
            return Application::getInstance();
        }

        return Application::getInstance()->make($type);
    }
}

if (!function_exists('root_dir')) {
    function root_dir(string $path = null)
    {
        return app()->rootDirectory($path);
    }
}

if (!function_exists('cache_dir')) {
    function cache_dir(string $path = null)
    {
        return app()->rootDirectory('_cache'.DIRECTORY_SEPARATOR.trim($path, DIRECTORY_SEPARATOR));
    }
}

if (!function_exists('public_dir')) {
    function public_dir(string $path = null)
    {
        return app()->rootDirectory('public'.DIRECTORY_SEPARATOR.trim($path, DIRECTORY_SEPARATOR));
    }
}

if (!function_exists('content_dir')) {
    function content_dir(string $path = null)
    {
        return app()->rootDirectory('content'.DIRECTORY_SEPARATOR.trim($path, DIRECTORY_SEPARATOR));
    }
}

if (!function_exists('config')) {
    function config(string $key = null, $default = null)
    {
        if (!is_string($key)) {
            return app()->config();
        }

        return app()->config()->get($key, $default);
    }
}

if (!function_exists('event')) {
    function event(string $name, $payload = null) {
        return app(EventDispatcher::class)->dispatch($name, $payload);
    }
}

if (!function_exists('url')) {
    function url(string $path) {
        return \Znck\Sereno\Blade::urlDirective($path);
    }
}
