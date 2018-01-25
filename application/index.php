<?php

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\Request;

require_once __DIR__ . '/vendor/autoload.php';

Config::load(__DIR__);

$app = new Application(__DIR__);

require_once __DIR__ . '/inc/routes.php';

$app->run();
