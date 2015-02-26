<?php

namespace Piwik\GithubSync;

use ArrayComparator\ArrayComparator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
                InputArgument::REQUIRED,
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
        $to = $input->getArgument('to');
        $this->github = new Github($input->getOption('token'));

        $output->writeln(sprintf('<comment>Synchronizing labels from %s to %s</comment>', $from, $to));

        $fromLabels = $this->github->getLabels($from);
        $toLabels = $this->github->getLabels($to);

        $comparator = new ArrayComparator();

        // Labels identity is their name, so they are the same if they have the same name
        $comparator->setItemIdentityComparator(function ($key1, $key2, $label1, $label2) {
            return strcasecmp($label1['name'], $label2['name']) === 0;
        });
        // Same labels have differences if they have a different color
        $comparator->setItemComparator(function ($label1, $label2) {
            return $label1['color'] === $label2['color'];
        });

        $comparator
            ->whenDifferent(function ($label1, $label2) use ($input, $output, $to) {
                $output->writeln(sprintf('Same label but different colors for <info>%s</info>', $label1['name']));

                if ($this->confirm($input, $output, 'Do you want to change the color of the label?')) {
                    $output->writeln('<error>Not implemented yet</error>');
                }
            })
            ->whenMissingRight(function ($label1) use ($input, $output, $to) {
                $output->writeln(sprintf('Missing label <info>%s</info> from %s', $label1['name'], $to));

                if ($this->confirm($input, $output, 'Do you want to create this missing label?')) {
                    $this->createLabel($output, $to, $label1['name'], $label1['color']);
                }
            })
            ->whenMissingLeft(function ($label2) use ($input, $output, $to) {
                $output->writeln(sprintf('Extra label <info>%s</info> in %s', $label2['name'], $to));

                if ($this->confirm($input, $output, 'Do you want to <fg=red>delete</fg=red> this extra label?')) {
                    $output->writeln('<error>Not implemented yet</error>');
                }
            });

        $comparator->compare($fromLabels, $toLabels);

        $output->writeln('<comment>Finished</comment>');
    }

    private function createLabel(OutputInterface $output, $repository, $name, $color)
    {
        if (! $this->github->isAuthenticated()) {
            $output->writeln('<error>Impossible because you are not authenticated. You need to provide a personal access token using the "--token" option. Create a token at https://github.com/settings/applications</error>');
            return;
        }

        $this->github->createLabel($repository, $name, $color);

        $output->writeln('<info>Label created</info>');
    }

    private function confirm($input, $output, $message)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>' . $message . '</question>', true);
        return $helper->ask($input, $output, $question);
    }
}
