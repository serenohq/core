<?php

namespace Znck\Sereno;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;
use Symfony\Component\Console\Application as Console;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Znck\Sereno\Builders\PageBuilder;
use Znck\Sereno\Commands\BuildCommand;
use Znck\Sereno\Commands\DeployCommand;
use Znck\Sereno\Commands\NewPostCommand;
use Znck\Sereno\Contracts\Builder;

class Application extends Container
{
    const VERSION = '0.0.0';

    /**
     * @var \Symfony\Component\Console\Application
     */
    protected $console;
    /**
     * @var string
     */
    protected $rootDirectory;

    protected $verboseLevel;
    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutputInterface
     */
    protected $output;

    public function start(string $directory) {
        $this->rootDirectory = $directory;

        $this->configureConsole();
        $this->configureApplication();

        $this->console->run();
    }

    protected function configureConsole() {
        $this->console = new Console('Sereno', self::VERSION);

        $this->console
            ->getDefinition()
            ->addOptions([
                             new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL,
                                             'The environment to operate in.', 'default'),
                         ]);

        $dispatcher = new EventDispatcher();
        $this->instance(EventDispatcher::class, $dispatcher);
        $this->console->setDispatcher($dispatcher);
        $this->registerCommands();
    }

    protected function configureApplication() {
        $this->singleton(Filesystem::class, function () {
            $this->line('Create filesystem.');

            return new Filesystem();
        });
        $this->singleton(Repository::class, function () {
            $this->line('Create config repository.');

            return new Repository(require __DIR__.'/config.php');
        });

        $this->loadConfigFileForEnv(null);
        $this->listen(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            $this->output = $event->getOutput();
            $this->setVerbosity($event->getOutput()->getVerbosity());
            $this->loadConfigFileForEnv($event->getInput()->getOption('env'));
            $this->bootApplication();
        });
    }

    protected function registerCommands() {
        $this->console->addCommands(
            array_merge(
                [
                    new BuildCommand(),
                    new NewPostCommand(),
                    new DeployCommand(),
                ],
                config('sereno.commands', [])
            ));
    }

    public function listen($event, $listener) {
        $this->make(EventDispatcher::class)->addListener($event, $listener);
    }

    protected function setVerbosity($verbosity) {
        $this->verboseLevel = $verbosity;
    }

    protected function loadConfigFileForEnv($env) {
        $filesystem = $this->make(Filesystem::class);

        if (in_array($env, [null, 'default'])) {
            $configFile = $this->rootDirectory('config.php');
        } else {
            $configFile = $this->rootDirectory("config.${env}.php");
        }

        $this->line("Loading config file: <info>${configFile}</info>");
        if ($filesystem->exists($configFile)) {
            $configs = $filesystem->getRequire($configFile);
            $this->mergeConfig($configs);
        }
    }

    protected function loadApplicationConfiguration(array $settings) {
        $config = $this->config();
        foreach ($settings as $name => $setting) {
            if (is_array($original = $config->get('sereno.'.$name))) {
                $config->set('sereno.'.$name, array_merge($original, $setting));
            } else {
                $config->set('sereno.'.$name, $setting);
            }
        }
    }

    protected function bootApplication() {
        $this->registerServices();
        $this->registerProcessors();
        $this->registerExtractors();
        $this->registerBuilders();

        $this->line('<info>Ready.</info>'.PHP_EOL);
    }

    public function rootDirectory(string $path = null) : string {
        return $this->rootDirectory.(is_null($path) ? '' : DIRECTORY_SEPARATOR.$path);
    }

    public function line(string $line) {
        if ($this->verboseLevel >= OutputInterface::VERBOSITY_VERBOSE) {
            if ($this->output) {
                $this->output->writeln($line);
            } else {
                print_r($line.PHP_EOL);
            }
        }
    }

    public function config() : Repository {
        return $this->make(Repository::class);
    }

    protected function registerServices() {
        $this->line('Boot services.');
        $this->singleton(Factory::class, function () {
            $this->line('Create view factory');

            return $this->createViewFactory();
        });
    }

    private function registerProcessors() {
        $this->line('Boot processors.');
        $this->singleton(ProcessorFactory::class, function () {
            $this->line('Create processor factory');

            $factory = new ProcessorFactory();

            $factory->registerDefaultProcessor($this->make(config('sereno.default_processor')));

            foreach (config('sereno.processors') as $processor) {
                $factory->register($this->make($processor));
            }

            return $factory;
        });
    }

    protected function registerExtractors() {
        $this->line('Boot extractors.');
        $this->singleton(DataExtractor::class, function () {
            $this->line('Create data extractor');

            $factory = new DataExtractor();
            foreach (config('sereno.extractors') as $extractor) {
                $factory->register($this->make($extractor));
            }

            return $factory;
        });
    }

    protected function registerBuilders() {
        $this->line('Boot builders.');
        $this->singleton(SiteGenerator::class, function () {
            $this->line('Create site generator');

            $generator = new SiteGenerator($this, $this->make(Filesystem::class), $this->make(Factory::class));
            $builders = array_merge([PageBuilder::class], config('sereno.builders'));

            foreach ($builders as $class) {
                $builder = $this->make($class);

                if ($builder instanceof Builder) {
                    $generator->register($builder);
                } else {
                    throw new InvalidArgumentException("${class} should be instance of ".Builder::class);
                }
            }

            return $generator;
        });
    }

    protected function createViewFactory(): Factory {
        $resolver = new EngineResolver();

        $compiler = $this->createBladeCompiler();

        $resolver->register('blade', function () use ($compiler) {
            return new CompilerEngine($compiler);
        });

        $dispatcher = new Dispatcher();

        $dispatcher->listen('creating: *', function () {
            /*
             * On rendering Blade views we will mute error reporting as
             * we don't care about undefined variables or type
             * mistakes during compilation.
             */
            error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
        });

        $filesystem = $this->make(Filesystem::class);
        $finder = new FileViewFinder($filesystem, [content_dir(), __DIR__.'/../resources/views']);

        return new Factory($resolver, $finder, $dispatcher);
    }

    protected function createBladeCompiler() {
        $cache = cache_dir();
        $filesystem = $this->make(Filesystem::class);

        if (!$filesystem->isDirectory($cache)) {
            $filesystem->makeDirectory($cache);
        }

        $blade = new Blade(new BladeCompiler($filesystem, $cache));

        return $blade->getCompiler();
    }

    protected function mergeConfig(array $configs, string $prefix = '') {
        $repository = $this->config();

        foreach ($configs as $name => $value) {
            $key = $prefix.$name;
            if (hash_equals('sereno', $key)) {
                $this->loadApplicationConfiguration((array)$value);
            } elseif (is_array($value)) {
                $this->mergeConfig($value, $name.'.');
            } else {
                $repository->set($key, $value);
            }
        }
    }
}
