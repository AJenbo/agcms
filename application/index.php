<?php

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\Controller\Site;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/vendor/autoload.php';

Config::load(__DIR__);

$app = new Application(__DIR__);
$app->addRoute('GET', '/', Site::class, 'frontPage');
$app->addRoute('GET', '/kat(\d+)-[^/]*/', Site::class, 'category');
$app->addRoute('GET', '/side(\d+)-[^/]*\.html', Site::class, 'rootPage');
$app->addRoute('GET', '/kat(\d+)-[^/]*/side(\d+)-[^/]*\.html', Site::class, 'page');
$app->addRoute('GET', '/mærke(\d+)-[^/]*/', Site::class, 'brand');
$app->addRoute('GET', '/krav/(\d+)/[^/]*.html', Site::class, 'requirement');
$app->run(Request::createFromGlobals());
