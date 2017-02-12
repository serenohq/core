<?php

namespace Sereno\Commands;

use Illuminate\Filesystem\Filesystem;
use Sereno\SiteGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    public function configure()
    {
        $this->setName('build')
             ->setDescription('Generate the static files')
             ->addOption('force', null, InputOption::VALUE_NONE, 'Clear the cache before building');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            app(Filesystem::class)->cleanDirectory(cache_dir());
        }

        $locales = config('_translations');

        if (is_array($locales)) {
            $this->generateMultiLingualSite($locales);
        } else {
            app(SiteGenerator::class)->build();
        }

        $output->writeln('<info>Site was generated successfully.</info>');
    }

    /**
     * @param $locales
     */
    protected function generateMultiLingualSite(array $locales)
    {
        $default = config('sereno.url');
        $public = config('sereno.public');
        foreach ($locales as $config) {
            app('translator')->setLocale($config['locale']);
            config()->set('sereno.url', $this->getUrl($default, $config['url']));
            config()->set('sereno.public', $this->getDir($public, $config['dir'] ?? null, $config['url']));

            app(SiteGenerator::class)->build();
        }
        config()->set('sereno.url', $default);
        config()->set('sereno.public', $public);
    }

    protected function getUrl(string $default, string $url)
    {
        if (starts_with($url, ['http://', 'https://'])) {
            return $url;
        }

        return rtrim($default, '/').'/'.ltrim($url, '/');
    }

    protected function getDir(string $public, string $dir = null, string $url)
    {
        if (is_null($dir)) {
            $dir = $url;
        }

        return trim($public, '\/').DIRECTORY_SEPARATOR.trim($dir, '\/');
    }
}
