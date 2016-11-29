<?php

namespace Znck\Sereno\Commands;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DeployCommand extends Command
{
    protected $directory;

    public function configure()
    {
        $this->setName('deploy')
             ->setDescription('Deploy the site');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->directory = $directory = 'deploy-'.time();
        $repository = config('github.repository') ?? $this->getRepository();
        $branch = config('github.branch') ?? $this->getBranch();
        $author = config('github.author') ?? 'Rahul Kadyan <hi@znck.me>';

        $this->build($output);

        $this->prepareRepository($directory, $repository, $branch);

        $commands = [
            "git add -A" => null,
            "git commit -m ':rocket: Sereno Auto Deploy' --author='${author}'" => null,
            "git push origin ${branch}" => "Uploading...",
        ];

        foreach ($commands as $command => $name) {
            $process = new Process($command);
            $process->setWorkingDirectory(root_dir($directory));
            if (is_string($name)) $output->writeln($name);
            $process->run();
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln('=> '.$command);
                $output->write($process->getOutput());
            }
            if (! $process->isSuccessful()) {
                $this->dd($output, $process);
            }
        }

        $filesystem->deleteDirectory($directory);

        $output->writeln('<info>Site was generated successfully.</info>');
    }

    protected function getRepository() {
        $process = new Process('git remote get-url --push origin');
        $process->run();

        return trim($process->getOutput());
    }

    protected function getBranch() {
        $process = new Process('git rev-parse --abbrev-ref HEAD');
        $process->run();

        return hash_equals('master', trim($process->getOutput())) ? 'gh-pages' : 'master';
    }

    protected function prepareRepository(string $directory, string $repository, string $branch) {
        $clone = new Process("git clone --depth=1 -b ${branch} ${repository} ${directory}");
        $clone->setWorkingDirectory(root_dir());
        $clone->run();


        $filesystem = app(Filesystem::class);
        if ($clone->isSuccessful()) {
            $filesystem->copyDirectory(root_dir('public'), root_dir($directory));
            return;
        }

        $filesystem->makeDirectory(root_dir($directory));
        $filesystem->copyDirectory(root_dir('public'), root_dir($directory));

        $init = new Process("git init; git checkout -b ${branch}; git remote add origin ${repository}");
        $init->setWorkingDirectory(root_dir($directory));
        $init->run();
    }

    protected function dd(OutputInterface $output, Process $process)
    {
        $output->writeln('<error>There was some error.</error>'.PHP_EOL.$process->getErrorOutput());
        $filesystem = app(Filesystem::class);
        if ($filesystem->exists(root_dir($this->directory))) {
            $filesystem->deleteDirectory(root_dir($this->directory));
        }
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
