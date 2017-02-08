<?php

namespace Sereno\Commands;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    public function configure()
    {
        $this->setName('init')
             ->setDescription('Create a new sereno project in current project')
             ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing sereno.yml');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $filesystem = app(Filesystem::class);
        $filename = root_dir('sereno.yml');

        if (! $force and $filesystem->exists($filename)) {
            $output->writeln('<error>This is a sereno project. Use --force to re-create.</error>');
            exit(-1);
        }

        $serenoDir = root_dir('.github/sereno');
        if (! $filesystem->exists($serenoDir)) {
            $filesystem->makeDirectory($serenoDir, 0755, true);
        }

        $filesystem->copyDirectory(__DIR__.'/../../resources/assets', root_dir('.github/sereno/resources/assets'));
        $filesystem->copy(__DIR__.'/../../resources/gulpfile.js', root_dir('.github/sereno/gulpfile.js'));

        $packageFile = root_dir('package.json');
        if (! $force and $filesystem->exists($packageFile)) {
            $output->writeln('<info>Add script to package.json:</info>'.PHP_EOL.
                             '   "sereno:dev": "gulp --gulpfile .github/sereno/gulpfile.js watch",'.PHP_EOL.
                             '   "sereno:build": "gulp --gulpfile .github/sereno/gulpfile.js",,'.PHP_EOL.
                             '   "sereno:deploy": "npm run sereno:build && sereno deploy"');
            $output->writeln('<info>Run command:</info>'.PHP_EOL.
                             '   yarn add --dev gulp laravel-elixir yargs bootstrap highlightjs jquery tether');
        } else {
            $filesystem->copy(__DIR__.'/../../resources/package.json', $packageFile);
        }

        $filesystem->put($filename, $this->getConfigFileContent());
        $output->writeln('<info>Sereno project created.</info> Checkout sereno.yml');
    }

    public function getConfigFileContent()
    {
        return <<<'EOF'
siteName: [Project Name]
siteDescription: [Project Description]

sereno:
    url: /
    public: .github/sereno/public
    extensions:
      - Sereno\Extensions\DocsExtension
    directory:
      - .github/sereno/content
    views:
      - .github/sereno/resources/views/
    ignore:
      - docs/README.md

docs:
    directory: docs
    url_prefix: /
    index: index
    extends: docs.base
    yields: doc_content
    default: installation
EOF;
    }
}
