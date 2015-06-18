<?php

namespace Piwik\GithubSync;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Base class for synchronizing stuff.
 */
abstract class AbstractSynchronizer
{
    /**
     * @var Github
     */
    protected $github;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(Github $github, InputInterface $input, OutputInterface $output)
    {
        $this->github = $github;
        $this->input = $input;
        $this->output = $output;
    }

    abstract public function synchronize($from, $to);

    protected function confirm($message)
    {
        $helper = new QuestionHelper();
        $question = new ConfirmationQuestion('<question>' . $message . '</question>', true);
        return $helper->ask($this->input, $this->output, $question);
    }
}
