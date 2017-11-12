<?php

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\Controller\Admin\AdminController;
use AGCMS\Controller\Admin\ExplorerController;
use AGCMS\Controller\Admin\PageController;
use AGCMS\Controller\Admin\SiteTreeController;
use AGCMS\Controller\Admin\InvoiceController;
use AGCMS\Controller\Ajax;
use AGCMS\Controller\Feed;
use AGCMS\Controller\Payment;
use AGCMS\Controller\Search;
use AGCMS\Controller\Shopping;
use AGCMS\Controller\Site;
use AGCMS\Request;

require_once __DIR__ . '/vendor/autoload.php';

Config::load(__DIR__);

$app = new Application(__DIR__);

// Set up site routes
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
$app->addRoute('GET', '/search/', Search::class, 'index');
$app->addRoute('GET', '/search/results/', Search::class, 'results');
$app->addRoute('GET', '/order/', Shopping::class, 'basket');
$app->addRoute('GET', '/order/address/', Shopping::class, 'address');
$app->addRoute('POST', '/order/send/', Shopping::class, 'send');
$app->addRoute('GET', '/betaling/', Payment::class, 'index');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/', Payment::class, 'basket');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/address/', Payment::class, 'address');
$app->addRoute('POST', '/betaling/(\d+)/([^/]+)/address/', Payment::class, 'addressSave');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/terms/', Payment::class, 'terms');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/status/', Payment::class, 'status');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/callback/', Payment::class, 'callback');

// Admin pages
$app->addRoute('GET', '/admin/', AdminController::class, 'index');
$app->addRoute('GET', '/admin/editpage/', PageController::class, 'index');
$app->addRoute('POST', '/admin/editpage/', PageController::class, 'createPage');
$app->addRoute('GET', '/admin/editpage/(\d+)/', PageController::class, 'index');
$app->addRoute('PUT', '/admin/editpage/(\d+)/', PageController::class, 'updatePage');
$app->addRoute('GET', '/admin/editpage/pagelist/', PageController::class, 'pageList');
$app->addRoute('GET', '/admin/sitetree/', SiteTreeController::class, 'index');
$app->addRoute('GET', '/admin/sitetree/([-\d]+)/lable/', SiteTreeController::class, 'lable');
$app->addRoute('GET', '/admin/explorer/', ExplorerController::class, 'index');
$app->addRoute('GET', '/admin/explorer/folders/', ExplorerController::class, 'folders');
$app->addRoute('POST', '/admin/explorer/folders/', ExplorerController::class, 'folderCreate');
$app->addRoute('DELETE', '/admin/explorer/folders/', ExplorerController::class, 'folderDelete');
$app->addRoute('PUT', '/admin/explorer/folders/', ExplorerController::class, 'folderRename');
$app->addRoute('GET', '/admin/explorer/files/', ExplorerController::class, 'files');
$app->addRoute('DELETE', '/admin/explorer/files/(\d+)/', ExplorerController::class, 'fileDelete');
$app->addRoute('GET', '/admin/explorer/files/(\d+)/view/', ExplorerController::class, 'fileView');
$app->addRoute('GET', '/admin/explorer/files/(\d+)/move/', ExplorerController::class, 'fileMoveDialog');
$app->addRoute('PUT', '/admin/explorer/files/(\d+)/move/', ExplorerController::class, 'fileRename');
$app->addRoute('GET', '/admin/explorer/search/', ExplorerController::class, 'search');
$app->addRoute('GET', '/admin/invoices/(\d+)/pdf/', InvoiceController::class, 'pdf');

$app->run(Request::createFromGlobals());
