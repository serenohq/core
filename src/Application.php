<?php

namespace Sereno;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Sereno\Extensions\DefaultExtension;
use Symfony\Component\Console\Application as Console;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Yaml;

class Application extends Container
{
    const VERSION = '0.3.2';

    use Traits\RegisterExtensionsTrait;

    /**
     * @var Console
     */
    protected $console;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var int
     */
    protected $verboseLevel;

    /**
     * @var ConsoleOutputInterface
     */
    protected $output;

    public function start(string $directory)
    {
        $this->setPath($directory);

        $this->configureConsole();
        $this->configureApplication();
        $this->listen(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            $this->onConsoleCommand($event);
        });

        $this->console->run();
    }

    public function setPath(string $dir)
    {
        $this->path = rtrim($dir, DIRECTORY_SEPARATOR);
        $this->verbose(" :: Change path to: {$this->path}");
    }

    public function configureApplication()
    {
        $this->verbose(' :: Configure application.');
        $this->instance(Filesystem::class, new Filesystem());
        $this->instance(Repository::class, new Repository(require __DIR__.'/config.php'));
    }

    public function configureConsole()
    {
        $this->console = new Console('Sereno', self::VERSION);

        $this->console
            ->getDefinition()
            ->addOptions([
                             new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL,
                                             'The environment to operate in.', 'default'),
                             new InputOption('--dir', '-d', InputOption::VALUE_OPTIONAL,
                                             'Project root directory.', null),
                         ]);

        $this->instance(EventDispatcher::class, new EventDispatcher());
        $dispatcher = $this->make(EventDispatcher::class);
        $this->console->setDispatcher($dispatcher);
        $this->registerCommands();
    }

    public function listen($event, $listener)
    {
        $this->make(EventDispatcher::class)->addListener($event, $listener);
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $this->output = $event->getOutput();
        $this->setVerbosity($this->output->getVerbosity());

        $this->console('<info>Sereno</info> '.static::VERSION);

        $this->verbose('Boot Sereno...');

        $directory = realpath($event->getInput()->getOption('dir'));
        if (is_string($directory) and strlen($directory)) {
            $this->setPath($directory);
        }

        $env = $event->getInput()->getOption('env');

        if (! in_array(get_class($event->getCommand()), [
            Commands\InitCommand::class,
            \Symfony\Component\Console\Command\ListCommand::class,
        ])) {
            $this->exitIfNotValidProject();
        }

        $this->loadConfigFileForEnv(null);

        if ($env !== 'default') {
            $this->loadConfigFileForEnv($env);
        }

        $this->bootApplication();
    }

    public function console(string $line)
    {
        if ($this->output) {
            $this->output->writeln($line);
        } else {
            print_r($line.PHP_EOL);
        }
    }

    public function line(string $line)
    {
        if ($this->verboseLevel >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->console($line);
        }
    }

    public function verbose(string $line)
    {
        if ($this->verboseLevel >= OutputInterface::VERBOSITY_DEBUG) {
            $this->line($line);
        }
    }

    protected function registerCommands()
    {
        $this->console->addCommands([
                                        new Commands\BuildCommand(),
                                        new Commands\NewPostCommand(),
                                        new Commands\DeployCommand(),
                                        new Commands\OverrideCommand(),
                                        new Commands\InitCommand(),
                                    ]);
    }

    public function setVerbosity($verbosity)
    {
        $this->verboseLevel = $verbosity;
    }

    protected function exitIfNotValidProject()
    {
        if (! $this->make(Filesystem::class)->exists($this->rootDirectory('sereno.yml'))) {
            $this->output->writeln('<error>This is not as sereno project:</error> '.$this->path);
            exit(-1);
        }
    }

    protected function loadConfigFileForEnv($env)
    {
        $filesystem = $this->make(Filesystem::class);

        if (in_array($env, [null, 'default'])) {
            $configFile = $this->rootDirectory('sereno.yml');
        } else {
            $configFile = $this->rootDirectory("sereno.${env}.yml");
        }

        if ($filesystem->exists($configFile)) {
            $this->verbose(" :: Loading config file: <info>${configFile}</info>");
            $configs = (array) Yaml::parse($filesystem->get($configFile), Yaml::PARSE_OBJECT);
            $this->mergeConfig($configs);
        } else {
            $this->verbose(" :: Config file not found: <error>${configFile}</error>");
        }
    }

    public function bootApplication()
    {
        $this->bootExtensions();
        $this->configureContentsDirectory($this->getContentDirectory());
        $this->bootServices();
        $this->bootProcessors();
        $this->bootExtractors();
        $this->bootBuilders();

        $this->verbose('<info>Ready.</info>'.PHP_EOL);
        $this->verbose('Prepare Services...');
        $this->verbose('----------------------------');
    }

    public function rootDirectory(string $path = null) : string
    {
        $s = DIRECTORY_SEPARATOR;

        return $this->path.$s.trim((string) $path, $s);
    }

    protected function mergeConfig(array $configs, string $prefix = '')
    {
        $repository = $this->config();

        foreach ($configs as $name => $value) {
            $key = $prefix.$name;
            if (is_array($value) and array_values($value) !== $value) {
                $this->mergeConfig($value, $name.'.');
            } else {
                $repository->set($key, $value);
            }
        }
    }

    protected function configureContentsDirectory($directories = [])
    {
        $default = (array) config('sereno.directory', []);

        $this->config()->set('sereno.directory', array_merge($default, $directories));
    }

    protected function bootExtensions()
    {
        $this->verbose(' :: Boot extensions.');
        $extensions = (array) config('sereno.extensions');

        array_unshift($extensions, DefaultExtension::class);

        foreach (array_unique($extensions) as $name) {
            $extension = $this->make($this->normalizeExtension($name));

            $extension->boot($this);
        }
    }

    protected function normalizeExtension(string $name): string
    {
        if (str_contains($name, '\\')) {
            return $name;
        }

        return 'Sereno\\Extensions\\'.$name;
    }

    protected function bootServices()
    {
        $this->verbose(' :: Boot services.');
        $this->singleton('translation.loader', function () {
            $this->verbose('     - Create translation loader');

            return new FileLoader($this->make(Filesystem::class), root_dir(config('_translator.dir')));
        });
        $this->singleton('translator', function ($app) {
            $this->verbose('     - Create translator');

            $loader = $app['translation.loader'];
            $locale = config('_translator.locale');
            $fallback = config('_translator.fallback_locale');

            $trans = new Translator($loader, $locale);

            $trans->setFallback($fallback);

            return $trans;
        });
        $this->singleton(Factory::class, function () {
            $this->verbose('     - Create view factory');

            return $this->createViewFactory();
        });
    }

    private function bootProcessors()
    {
        $this->verbose(' :: Boot processors.');
        $this->singleton(ProcessorFactory::class, function () {
            $this->verbose('     - Create processor factory');

            $factory = new ProcessorFactory();

            foreach ($this->getProcessors() as $processor) {
                $this->verbose('        + Processor: '.$processor);
                $factory->register($this->make($processor));
            }

            return $factory;
        });
    }

    protected function bootExtractors()
    {
        $this->verbose(' :: Boot extractors.');
        $this->singleton(DataExtractor::class, function () {
            $this->verbose('     - Create data extractor');

            $factory = new DataExtractor();

            foreach ($this->getExtractors() as $extractor) {
                $this->verbose('        + Extractor: '.$extractor);
                $factory->register($this->make($extractor));
            }

            return $factory;
        });
    }

    protected function bootBuilders()
    {
        $this->verbose(' :: Boot builders.');
        $this->singleton(SiteGenerator::class, function () {
            $this->verbose('     - Create site generator');

            $generator = new SiteGenerator($this, $this->make(Filesystem::class), $this->make(Factory::class));

            foreach ($this->getBuilders() as $builder) {
                $generator->register($this->make($builder));
            }

            return $generator;
        });
    }

    public function config() : Repository
    {
        return $this->make(Repository::class);
    }

    protected function createViewFactory(): Factory
    {
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

        $directories = array_map(function ($name) {
            return root_dir($name);
        }, (array) config('sereno.views'));

        foreach ($this->getViewsDirectory() as $value) {
            array_push($directories, $value);
        }

        array_push($directories, dirname(__DIR__).'/resources/views');

        $directories = array_unique($directories);

        $this->verbose('     - View directories');
        foreach ($directories as $value) {
            $this->verbose('        + '.$value);
        }

        $filesystem = $this->make(Filesystem::class);
        $finder = new FileViewFinder($filesystem, $directories);

        return new Factory($resolver, $finder, $dispatcher);
    }

    protected function createBladeCompiler()
    {
        $cache = cache_dir();
        $filesystem = $this->make(Filesystem::class);

        $this->verbose("        + Using cache: ${cache}");
        $this->verbose('        + Public directory: '.public_dir());

        if (! $filesystem->isDirectory($cache)) {
            $filesystem->makeDirectory($cache);
        }

        $blade = new Blade(new BladeCompiler($filesystem, $cache));

        return $blade->getCompiler();
    }

    protected function loadApplicationConfiguration(array $settings)
    {
        $config = $this->config();
        foreach ($settings as $name => $setting) {
            if (is_array($original = $config->get('sereno.'.$name))) {
                $config->set('sereno.'.$name, array_merge($original, $setting));
            } else {
                $config->set('sereno.'.$name, $setting);
            }
        }
    }
}
