<?php

namespace Znck\Sereno\Commands;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DeployCommand extends Command
{
    public function configure()
    {
        $this->setName('deploy')
             ->setDescription('Deploy the site');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->build($output);

        $save = new Process("git add -A; git commit -m ':rocket: Sereno Auto Deploy'");
        $upload = new Process('git push -u origin');
        $publish = new Process('git subtree push --prefix public origin '.config('github.branch'));

        $output->writeln('=> '.$save->getCommandLine());
        $save->run();
        $output->write($save->getOutput());
        if (! $save->isSuccessful()) {
            $this->dd($output, $save);
        }

        $upload->run();
        $output->write($upload->getOutput());
        if (! $upload->isSuccessful()) {
            $this->dd($output, $upload);
        }

        $publish->run();
        $output->write($publish->getOutput());

        if ($publish->isSuccessful()) {
            $output->writeln('<info>Site was generated successfully.</info>');
        } else {
            $this->dd($output, $publish);
        }
    }

    protected function dd(OutputInterface $output, Process $process)
    {
        $output->writeln('<error>There was some error.</error>'.PHP_EOL.$process->getErrorOutput());
        exit($process->getExitCode());
    }

    protected function build(OutputInterface $output)
    {
        $output->writeln('Building website...');
        $gulp = new Process('./node_modules/.bin/gulp');
        app(Filesystem::class)->cleanDirectory(cache_dir());
        $gulp->run();
        if (! $gulp->isSuccessful()) {
            $output->writeln('<error>Javascript dependencies not installed</error>');
            $output->writeln('Run <info>yarn</info> or <info>npm install</info>');
            exit($gulp->getExitCode());
        }
    }
}
