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
        app(SiteGenerator::class)->build();

        $output->writeln('<info>Site was generated successfully.</info>');
    }
}
