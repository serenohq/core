<?php

namespace Znck\Sereno\Commands;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class NewPostCommand extends Command
{
    public function configure()
    {
        $this->setName('post')
             ->setDescription('Create new blog post')
             ->addOption('collection', 'c', InputOption::VALUE_NONE, 'Add to a series');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('<question>Title of the post:</question> ');

        $title = $helper->ask($input, $output, $question);
        $filename = date('Y-m-d').'-'.str_slug($title).'.md';
        $today = date('F d, Y');

        $filesystem = app(Filesystem::class);
        $directory = root_dir(config('blog.directory'));

        if ($input->getOption('collection')) {
            $collections = array_map(function ($file) {
                return basename($file);
            }, $filesystem->directories($directory));

            if (count($collections)) {
                $output->writeln("\n<comment>Your collections:</comment>");
                foreach ($collections as $collection) {
                    $output->writeln(" - ${collection}");
                }
                $output->writeln('');
            } else {
                $output->writeln("\n<error>No collections found! Create a new one.</error>\n");
            }

            $question = new Question('<question>Add to collection:</question> ', $collections);
            $question->setAutocompleterValues($collections);

            $collection = str_slug($helper->ask($input, $output, $question));

            $directory .= DIRECTORY_SEPARATOR.$collection;
        }

        if (!$filesystem->exists($directory)) {
            $filesystem->makeDirectory($directory, 0755, true);
        }

        $filename = $directory.DIRECTORY_SEPARATOR.$filename;
        $relative = str_replace(root_dir().DIRECTORY_SEPARATOR, '', $filename);

        if ($filesystem->exists($filename)) {
            $output->writeln("<error>File ${relative} already exists.</error>");
            exit(-1);
        }

        $filesystem->put($filename, <<<EOF
---
pageTitle: ${title} -
post:
    title: ${title}
    date: ${today}
    brief: Write post summary.
---

> Start writing...
EOF
        );


        $output->writeln("New post created in <info>${relative}</info>. Start writing...");
    }
}
