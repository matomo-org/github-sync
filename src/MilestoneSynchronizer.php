<?php

namespace Piwik\GithubSync;

use ArrayComparator\ArrayComparator;

/**
 * Synchronizes milestones.
 */
class MilestoneSynchronizer extends AbstractSynchronizer
{

    public function synchronize($from, $to)
    {
        $fromMilestones = $this->github->getMilestones($from);
        $toMilestones = $this->github->getMilestones($to);

        $comparator = new ArrayComparator();

        // Milestones identity is their name, so they are the same if they have the same name
        $comparator->setItemIdentityComparator(function ($key1, $key2, $milestone1, $milestone2) {
            return $milestone1['title'] === $milestone2['title'];
        });

        $comparator
            ->whenMissingRight(function ($milestone) use ($to) {
                $this->output->writeln(sprintf('Missing milestone <info>%s</info> from %s', $milestone['title'], $to));

                if ($this->confirm('Do you want to create this missing milestone?')) {
                    $this->createMilestone($to, $milestone['title'], $milestone['state']);
                }
            })
            ->whenMissingLeft(function ($milestone) use ($to) {
                $this->output->writeln(sprintf('Extra milestone <info>%s</info> in %s', $milestone['title'], $to));

                if ($this->confirm('Do you want to <fg=red>delete</fg=red> this extra milestone?')) {
                    $this->deleteMilestone($to, $milestone);
                }
            });

        $comparator->compare($fromMilestones, $toMilestones);
    }

    private function createMilestone($repository, $title, $state)
    {
        try {
            $this->github->createMilestone($repository, $title, $state);

            $this->output->writeln('<info>Milestone created</info>');
        } catch (AuthenticationRequiredException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }

    private function deleteMilestone($repository, $milestone)
    {
        try {
            $this->github->deleteMilestone($repository, $milestone['number']);

            $this->output->writeln('<info>Milestone deleted</info>');
        } catch (AuthenticationRequiredException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }
}
