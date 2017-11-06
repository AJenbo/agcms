<?php

use AGCMS\Application;
use AGCMS\Config;

require_once __DIR__ . '/../application/vendor/autoload.php';

Config::load(realpath(__DIR__ . '/../application/'));

$app = new Application(realpath(__DIR__));
