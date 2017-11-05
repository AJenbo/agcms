<?php

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\Controller\Ajax;
use AGCMS\Controller\Feed;
use AGCMS\Controller\Site;
use AGCMS\Controller\Shopping;
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
$app->addRoute('GET', '/ajax/category/(\d+)/table/(\d+)/(\d+)', Ajax::class, 'table');
$app->addRoute('GET', '/ajax/category/(\d+)/([^/]+)', Ajax::class, 'category');
$app->addRoute('GET', '/ajax/address/([0-9+\s]+)', Ajax::class, 'address');
$app->addRoute('GET', '/opensearch.xml', Feed::class, 'openSearch');
$app->addRoute('GET', '/sitemap.xml', Feed::class, 'siteMap');
$app->addRoute('GET', '/feed/rss/', Feed::class, 'rss');
$app->addRoute('GET', '/order/', Shopping::class, 'basket');
$app->addRoute('GET', '/order/address/', Shopping::class, 'address');
$app->addRoute('POST', '/order/send/', Shopping::class, 'send');
$app->run(Request::createFromGlobals());
