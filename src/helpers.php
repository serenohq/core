<?php

use Sereno\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;

if (! function_exists('app')) {
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

if (! function_exists('debug')) {
    function debug($any)
    {
        if (is_array($any)) {
            $any = print_r($any, true);
        }

        app()->line((string) $any);
    }
}

if (! function_exists('root_dir')) {
    function root_dir(string $path = null)
    {
        return app()->rootDirectory($path);
    }
}

if (! function_exists('cache_dir')) {
    function cache_dir(string $path = null)
    {
        return app()->rootDirectory(config('sereno.cache', '_cache').DIRECTORY_SEPARATOR.trim($path, DIRECTORY_SEPARATOR));
    }
}

if (! function_exists('public_dir')) {
    function public_dir(string $path = null)
    {
        return app()->rootDirectory(config('sereno.public').DIRECTORY_SEPARATOR.trim($path, DIRECTORY_SEPARATOR));
    }
}

if (! function_exists('config')) {
    function config(string $key = null, $default = null)
    {
        if (! is_string($key)) {
            return app()->config();
        }

        return app()->config()->get($key, $default);
    }
}

if (! function_exists('event')) {
    function event(string $name, $payload = null)
    {
        return app(EventDispatcher::class)->dispatch($name, $payload);
    }
}

if (! function_exists('url')) {
    function url(string $path)
    {
        return \Sereno\Blade::urlDirective($path);
    }
}

if (! function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param string $id
     * @param array  $replace
     * @param string $locale
     *
     * @return \Illuminate\Contracts\Translation\Translator|string
     */
    function trans($id = null, $replace = [], $locale = null)
    {
        if (is_null($id)) {
            return app('translator');
        }

        return app('translator')->trans($id, $replace, $locale);
    }
}
if (! function_exists('trans_choice')) {
    /**
     * Translates the given message based on a count.
     *
     * @param string               $id
     * @param int|array|\Countable $number
     * @param array                $replace
     * @param string               $locale
     *
     * @return string
     */
    function trans_choice($id, $number, array $replace = [], $locale = null)
    {
        return app('translator')->transChoice($id, $number, $replace, $locale);
    }
}

//if (! function_exists('__')) {
//    /**
//     * Translate the given message.
//     *
//     * @param  string  $key
//     * @param  array  $replace
//     * @param  string  $locale
//     * @return \Illuminate\Contracts\Translation\Translator|string
//     */
//    function __($key = null, $replace = [], $locale = null)
//    {
//        return app('translator')->getFromJson($key, $replace, $locale);
//    }
//}
