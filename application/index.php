<?php

use App\Application;
use App\Services\ConfigService;

require_once __DIR__ . '/vendor/autoload.php';

ConfigService::load(__DIR__);

$app = new Application(__DIR__);

$app->run();
