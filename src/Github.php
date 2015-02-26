<?php

namespace Piwik\GithubSync;

use Github\Client;
use Github\Exception\RuntimeException;

/**
 * GitHub API.
 */
class Github
{
    /**
     * @var Client
     */
    private $github;

    /**
     * @var bool
     */
    private $authenticated = false;

    /**
     * @param string|null $token
     */
    public function __construct($token = null)
    {
        $this->github = new Client();

        if ($token) {
            $this->authenticate($token);
        }
    }

    public function getLabels($repository)
    {
        $array = explode('/', $repository, 2);

        try {
            return $this->github->issue()->labels()->all($array[0], $array[1]);
        } catch (RuntimeException $e) {
            throw new \RuntimeException('Error getting labels from repository ' . $repository, 0, $e);
        }
    }

    public function createLabel($repository, $name, $color)
    {
        $array = explode('/', $repository, 2);

        $this->github->issue()->labels()->create($array[0], $array[1], [
            'name'  => $name,
            'color' => $color,
        ]);
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    private function authenticate($token)
    {
        $this->github->authenticate($token, null, Client::AUTH_HTTP_TOKEN);
        $this->authenticated = true;
    }
}
