<?php

namespace Sereno\Commands;

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
        $this->build($output);

        $this->directory = $directory = 'deploy-'.time();
        $repository = config('github.repository') ?? $this->getRepository();
        $branch = config('github.branch') ?? $this->getBranch();
        $name = config('github.user.name') ?? 'Sereno Deployer';
        $email = config('github.user.email') ?? 'builtwith@sereno.in';

        $date = date('d M, Y - H:i:s T');
        $message = ":rocket: Sereno Auto Deploy (${date})\n\n[ci skip] [skip ci]";


        $this->prepareRepository($directory, $repository, $branch);

        $commands = [
            'git add -A'                                                                 => 'Search for changes',
            "git -c user.name='${name}' -c user.email='${email}' commit -m '${message}'" => 'Save changes',
            "git push origin ${branch}"                                                  => 'Uploading',
        ];

        foreach ($commands as $command => $name) {
            $process = new Process($command);
            $process->setWorkingDirectory(root_dir($directory));
            debug('=> '.$name.': '.$command);
            $process->run();

            if (! $process->isSuccessful()) {
                if (strpos($process->getOutput(), 'nothing to commit') !== false) {
                    $output->writeln('<info>Latest version is already deployed.</info>');
                    $this->cleanup();
                    exit(0);
                }

                $this->dd($output, $process);
            }
            if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln($name.'...');
            }
        }

        $this->cleanup();
        $output->writeln('<info>Site was generated successfully.</info>');
    }

    protected function getRepository()
    {
        $command = 'git remote get-url --push origin';
        $process = new Process($command);
        debug('=> Get Repository: '.$command);
        $process->run();

        return trim($process->getOutput());
    }

    protected function getBranch()
    {
        $command = 'git rev-parse --abbrev-ref HEAD';
        $process = new Process($command);
        debug('=> Get Branch: '.$command);
        $process->run();

        return hash_equals('master', trim($process->getOutput())) ? 'gh-pages' : 'master';
    }

    protected function prepareRepository(string $directory, string $repository, string $branch)
    {
        $command = "git clone --depth=1 -b ${branch} ${repository} ${directory}";
        $clone = new Process($command);
        $clone->setWorkingDirectory(root_dir());
        debug('=> Clone Repository: '.$command);
        $clone->run();

        $public = config('sereno.public');

        $filesystem = app(Filesystem::class);
        if ($clone->isSuccessful()) {
            $this->cleanOldBuild($directory);
            $filesystem->copyDirectory($public, root_dir($directory));

            return;
        }

        $filesystem->makeDirectory(root_dir($directory));
        $filesystem->copyDirectory(root_dir($public), root_dir($directory));

        debug('<error>Cloning failed.</error>');
        $command = "git init; git checkout -b ${branch}; git remote add origin ${repository}";
        $init = new Process($command);
        $init->setWorkingDirectory(root_dir($directory));
        debug('=> Create new Repository: '.$command);
        $init->run();
    }

    protected function dd(OutputInterface $output, Process $process)
    {
        $output->writeln('<error>There was some error.</error>'.PHP_EOL.$process->getErrorOutput());
        $this->cleanup();
        exit($process->getExitCode());
    }

    protected function cleanOldBuild($directory)
    {
        $directory = root_dir($directory);

        $filesystem = app(Filesystem::class);

        foreach ($filesystem->allFiles($directory) as $file) {
            $filesystem->delete($file);
        }

        foreach ($filesystem->directories($directory) as $file) {
            if (! starts_with($file, '.git')) {
                $filesystem->deleteDirectory($file);
            }
        }
    }

    protected function cleanup()
    {
        $filesystem = app(Filesystem::class);
        if ($filesystem->exists(root_dir($this->directory))) {
            $filesystem->deleteDirectory(root_dir($this->directory));
        }
    }

    protected function build(OutputInterface $output)
    {

        app(Filesystem::class)->deleteDirectory(cache_dir());

        $output->writeln('Building website...');
        $gulp = new Process('npm run sereno:build --env=default');
        debug('=> Build Script: npm run sereno:build');

        $gulp->run();
        if (! $gulp->isSuccessful()) {
            $output->writeln('<error>Javascript dependencies not installed</error>');
            $output->writeln('Run <info>yarn</info> or <info>npm install</info>');
            exit($gulp->getExitCode());
        }
    }
}
