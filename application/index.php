<?php

use AGCMS\Application;
use AGCMS\Config;

require_once __DIR__ . '/vendor/autoload.php';

Config::load(__DIR__);

$app = new Application(__DIR__);

$app->run();
