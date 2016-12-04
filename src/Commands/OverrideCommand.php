<?php

namespace Znck\Sereno\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Znck\Sereno\SiteGenerator;

class OverrideCommand extends Command
{
    public function configure()
    {
        $this->setName('override')
             ->setDescription('Override a Sereno (or theme) view')
             ->addArgument('name', InputArgument::REQUIRED, 'Name of the view. ex: blog.post')
             ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite exiting views');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $name = $input->getArgument('name');

        $directories = (array) config('sereno.views');

        if (count($directories) === 0) {
            $output->writeln('<error>Add your views directory to [sereno -> views] in config.php</error>');
            exit(-1);
        }

        $directory = array_first($directories);

        if (count($directories) > 1) {
            $question = new ChoiceQuestion('<question>Choose a views directory:</question> ', $directories, $directory);

            $helper = $this->getHelper('question');
            $directory = $helper->ask($input, $output, $question);
        }

        try {
            $original = $this->getViewFinder()->find($name);
        } catch (\InvalidArgumentException $e) {
            $output->writeln("<error>View [${name}] not found.</error>");
            exit(-1);
        }
        $overridden = root_dir($directory).DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $name).'.blade.php';

        if (!$force and $this->getFilesystem()->exists($overridden)) {
            $output->writeln("<error>View [${name}] is already overridden. Use --force to overwrite it.</error>");
            exit(-1);
        }

        $this->getFilesystem()->copy($original, $overridden);

        $filename = str_replace(root_dir().DIRECTORY_SEPARATOR, '', $overridden);
        $output->writeln("<info>The view [${name}] is overridden in ${filename}</info>");
    }

    protected function getFilesystem(): Filesystem {
        return app(Filesystem::class);
    }

    protected function getViewFinder(): FileViewFinder {
        $factory = app(Factory::class);

        return $factory->getFinder();
    }
}
