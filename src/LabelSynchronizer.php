<?php

namespace Piwik\GithubSync;

use ArrayComparator\ArrayComparator;
use Github\Exception\RuntimeException;

/**
 * Synchronizes labels.
 */
class LabelSynchronizer extends AbstractSynchronizer
{
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
                $this->output->writeln(sprintf(
                    'Same label but different name/color for <info>%s</info> (%s -> %s, %s -> %s)',
                    $label1['name'],
                    $label1['name'],
                    $label2['name'],
                    $label1['color'],
                    $label2['color']
                ));

                if ($this->confirm('Do you want to update the name and color of the label?')) {
                    $this->updateLabel($to, $label2['name'], $label1['name'], $label1['color']);
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
                    $this->deleteLabel($to, $label['name']);
                }
            });

        $comparator->compare($fromLabels, $toLabels);
    }

    private function createLabel($repository, $name, $color)
    {
        try {
            $this->github->createLabel($repository, $name, $color);

            $this->output->writeln('<info>Label created</info>');
        } catch (RuntimeException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }

    private function deleteLabel($repository, $name)
    {
        try {
            $this->github->deleteLabel($repository, $name);

            $this->output->writeln('<info>Label deleted</info>');
        } catch (RuntimeException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }

    private function updateLabel($repository, $name, $newName, $color)
    {
        try {
            $this->github->updateLabel($repository, $name, $newName, $color);

            $this->output->writeln('<info>Label updated</info>');
        } catch (RuntimeException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }
}
