<?php

namespace Piwik\GithubSync;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends Command
{
    /**
     * @var Github
     */
    private $github;

    protected function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('Synchronize GitHub labels and milestones between repositories')
            ->addArgument(
                'from',
                InputArgument::REQUIRED,
                'The repository containing the labels and milestones to use'
            )
            ->addArgument(
                'to',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The repository to synchronize'
            )
            ->addOption(
                'token',
                null,
                InputOption::VALUE_REQUIRED,
                'The GitHub token to use for authentication. Required if you want to create/updated/delete.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getArgument('from');
        $targetList = $input->getArgument('to');
        $this->github = new Github($input->getOption('token'));

        $labelSynchronizer = new LabelSynchronizer($this->github, $input, $output);

        foreach ($targetList as $to) {
            $output->writeln(sprintf('<comment>Synchronizing labels from %s to %s</comment>', $from, $to));

            $labelSynchronizer->synchronize($from, $to);

            $output->writeln('');
        }

        $output->writeln('<comment>Finished</comment>');
    }
}
