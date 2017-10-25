<?php

use AGCMS\Application;
use AGCMS\Config;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/vendor/autoload.php';

Config::load(__DIR__);

$app = new Application(__DIR__);
$app->run(Request::createFromGlobals());
