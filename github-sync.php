#!/usr/bin/env php
<?php

use Piwik\GithubSync\SyncCommand;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Application('GitHub Sync', 'dev');
$app->add(new SyncCommand());
$app->run();
