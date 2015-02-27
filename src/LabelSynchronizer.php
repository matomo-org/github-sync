<?php

namespace Piwik\GithubSync;

use ArrayComparator\ArrayComparator;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Synchronizes labels.
 */
class LabelSynchronizer
{
    /**
     * @var Github
     */
    private $github;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(Github $github, InputInterface $input, OutputInterface $output)
    {
        $this->github = $github;
        $this->input = $input;
        $this->output = $output;
    }

    public function synchronize($from, $to)
    {
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
            ->whenDifferent(function ($label1, $label2) use ($to) {
                $this->output->writeln(sprintf('Same label but different colors for <info>%s</info>', $label1['name']));

                if ($this->confirm('Do you want to change the color of the label?')) {
                    $this->output->writeln('<error>Not implemented yet</error>');
                }
            })
            ->whenMissingRight(function ($label) use ($to) {
                $this->output->writeln(sprintf('Missing label <info>%s</info> from %s', $label['name'], $to));

                if ($this->confirm('Do you want to create this missing label?')) {
                    $this->createLabel($to, $label['name'], $label['color']);
                }
            })
            ->whenMissingLeft(function ($label) use ($to) {
                $this->output->writeln(sprintf('Extra label <info>%s</info> in %s', $label['name'], $to));

                if ($this->confirm('Do you want to <fg=red>delete</fg=red> this extra label?')) {
                    $this->output->writeln('<error>Not implemented yet</error>');
                }
            });

        $comparator->compare($fromLabels, $toLabels);
    }

    private function createLabel($repository, $name, $color)
    {
        try {
            $this->github->createLabel($repository, $name, $color);

            $this->output->writeln('<info>Label created</info>');
        } catch (AuthenticationRequiredException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }

    private function confirm($message)
    {
        $helper = new QuestionHelper();
        $question = new ConfirmationQuestion('<question>' . $message . '</question>', true);
        return $helper->ask($this->input, $this->output, $question);
    }
}
