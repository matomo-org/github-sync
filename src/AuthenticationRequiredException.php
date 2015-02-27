<?php

namespace Piwik\GithubSync;

class AuthenticationRequiredException extends \Exception
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        $this->message = 'You are not authenticated. You need to provide a personal access token using the "--token" option. Create a token at https://github.com/settings/applications';
    }
}
