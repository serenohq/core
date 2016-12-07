<?php

use Illuminate\Filesystem\Filesystem;
use Sereno\Application;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    const ROOT_DIRECTORY = '/tmp/sereno';
    /**
     * @var Application
     */
    protected $app;

    public function setUp()
    {
        @mkdir(self::ROOT_DIRECTORY);

        $this->app = Application::getInstance();
        $this->app->setPath(self::ROOT_DIRECTORY);
        $this->app->configureApplication();
        $this->app->bootApplication();
    }

    protected function tearDown()
    {
        $this->app->make(Filesystem::class)->deleteDirectory(self::ROOT_DIRECTORY);
    }

    public function app($class, $args = [])
    {
        return $this->app->make($class, $args);
    }

    public function filesystem(): Filesystem
    {
        return $this->app(Filesystem::class);
    }

    public function getFile(string $content, string $name = 'foo')
    {
        $filename = self::ROOT_DIRECTORY.DIRECTORY_SEPARATOR.$name;

        $this->filesystem()->put($filename, $content);

        return new \Symfony\Component\Finder\SplFileInfo($name, self::ROOT_DIRECTORY, $filename);
    }
}
