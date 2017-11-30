<?php

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\Controller\Admin\AddressbookController;
use AGCMS\Controller\Admin\AdminController;
use AGCMS\Controller\Admin\BrandController;
use AGCMS\Controller\Admin\CategoryController;
use AGCMS\Controller\Admin\CustomPageController;
use AGCMS\Controller\Admin\ExplorerController;
use AGCMS\Controller\Admin\InvoiceController;
use AGCMS\Controller\Admin\MaintenanceController;
use AGCMS\Controller\Admin\PageController;
use AGCMS\Controller\Admin\RequirementController;
use AGCMS\Controller\Admin\SiteTreeController;
use AGCMS\Controller\Admin\TableController;
use AGCMS\Controller\Admin\UserController;
use AGCMS\Controller\Ajax;
use AGCMS\Controller\Feed;
use AGCMS\Controller\Payment;
use AGCMS\Controller\Search;
use AGCMS\Controller\Shopping;
use AGCMS\Controller\Site;
use AGCMS\Middleware\Auth;
use AGCMS\Middleware\Placekitten;
use AGCMS\Middleware\Utf8Url;
use AGCMS\Request;

require_once __DIR__ . '/vendor/autoload.php';

Config::load(__DIR__);

$app = new Application(__DIR__);
$app->addMiddleware('/', Utf8Url::class);
if ('develop' === Config::get('enviroment')) {
    $app->addMiddleware('/images/', Placekitten::class);
    $app->addMiddleware('/files/', Placekitten::class);
}

// Set up site routes
$app->addRoute('GET', '/', Site::class, 'frontPage');
$app->addRoute('GET', '/kat(\d+)-[^/]*/', Site::class, 'category');
$app->addRoute('GET', '/side(\d+)-[^/]*\.html', Site::class, 'rootPage');
$app->addRoute('GET', '/kat(\d+)-[^/]*/side(\d+)-[^/]*\.html', Site::class, 'page');
$app->addRoute('GET', '/mærke(\d+)-[^/]*/', Site::class, 'brand');
$app->addRoute('GET', '/krav/(\d+)/[^/]*.html', Site::class, 'requirement');
// Dynamic content
$app->addRoute('GET', '/ajax/category/(\d+)/table/(\d+)/(\d+)', Ajax::class, 'table');
$app->addRoute('GET', '/ajax/category/(\d+)/([^/]+)', Ajax::class, 'category');
$app->addRoute('GET', '/ajax/address/([0-9+\s]+)', Ajax::class, 'address');
// Search
$app->addRoute('GET', '/search/', Search::class, 'index');
$app->addRoute('GET', '/search/results/', Search::class, 'results');
// Shopping
$app->addRoute('GET', '/order/', Shopping::class, 'basket');
$app->addRoute('GET', '/order/address/', Shopping::class, 'address');
$app->addRoute('POST', '/order/send/', Shopping::class, 'send');
// Payment float
$app->addRoute('GET', '/betaling/', Payment::class, 'index');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/', Payment::class, 'basket');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/address/', Payment::class, 'address');
$app->addRoute('POST', '/betaling/(\d+)/([^/]+)/address/', Payment::class, 'addressSave');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/terms/', Payment::class, 'terms');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/status/', Payment::class, 'status');
$app->addRoute('GET', '/betaling/(\d+)/([^/]+)/callback/', Payment::class, 'callback');
// Feeds
$app->addRoute('GET', '/opensearch.xml', Feed::class, 'openSearch');
$app->addRoute('GET', '/sitemap.xml', Feed::class, 'siteMap');
$app->addRoute('GET', '/feed/rss/', Feed::class, 'rss');

// Admin backoffice
$app->addMiddleware('/admin/', Auth::class);
// Main index
$app->addRoute('GET', '/admin/', AdminController::class, 'index');
$app->addRoute('GET', '/admin/logout', AdminController::class, 'logout');
// Page editing
$app->addRoute('GET', '/admin/page/', PageController::class, 'index');
$app->addRoute('GET', '/admin/page/search/', PageController::class, 'search');
$app->addRoute('POST', '/admin/page/(\d+)/categories/(\d+)/', PageController::class, 'addToCategory');
$app->addRoute('DELETE', '/admin/page/(\d+)/categories/(\d+)/', PageController::class, 'removeFromCategory');
// Page CRUD
$app->addRoute('POST', '/admin/page/', PageController::class, 'createPage');
$app->addRoute('GET', '/admin/page/(\d+)/', PageController::class, 'index');
$app->addRoute('PUT', '/admin/page/(\d+)/', PageController::class, 'updatePage');
$app->addRoute('DELETE', '/admin/page/(\d+)/', PageController::class, 'delete');
// Accessory CD
$app->addRoute('POST', '/admin/page/(\d+)/accessories/(\d+)/', PageController::class, 'addAccessory');
$app->addRoute('DELETE', '/admin/page/(\d+)/accessories/(\d+)/', PageController::class, 'removeAccessory');
// Table
$app->addRoute('GET', '/admin/page/(\d+)/tables/', TableController::class, 'createDialog');
// Table C
$app->addRoute('POST', '/admin/tables/', TableController::class, 'create');
// Table row CUD
$app->addRoute('POST', '/admin/tables/(\d+)/row/', TableController::class, 'addRow');
$app->addRoute('PUT', '/admin/tables/(\d+)/row/(\d+)/', TableController::class, 'updateRow');
$app->addRoute('DELETE', '/admin/tables/(\d+)/row/(\d+)/', TableController::class, 'removeRow');
// Category editing
$app->addRoute('GET', '/admin/categories/', CategoryController::class, 'index');
// Category CRUD
$app->addRoute('GET', '/admin/categories/([-\d]+)/', CategoryController::class, 'index');
$app->addRoute('PUT', '/admin/categories/(\d+)/', CategoryController::class, 'update');
$app->addRoute('DELETE', '/admin/categories/(\d+)/', CategoryController::class, 'delete');
// Custom page RU
$app->addRoute('GET', '/admin/custom/(\d+)/', CustomPageController::class, 'index');
// Site tree
$app->addRoute('GET', '/admin/sitetree/', SiteTreeController::class, 'index');
$app->addRoute('GET', '/admin/sitetree/([-\d]+)/lable/', SiteTreeController::class, 'lable');
$app->addRoute('GET', '/admin/sitetree/([-\d]+)/', SiteTreeController::class, 'categoryContent');
$app->addRoute('GET', '/admin/sitetree/pageWidget/', SiteTreeController::class, 'pageWidget');
$app->addRoute('GET', '/admin/sitetree/inventory/', SiteTreeController::class, 'inventory');
// Requirement editing
$app->addRoute('GET', '/admin/requirement/list/', RequirementController::class, 'index');
$app->addRoute('GET', '/admin/requirement/', RequirementController::class, 'editPage');
// Requirement CRUD
$app->addRoute('POST', '/admin/requirement/', RequirementController::class, 'create');
$app->addRoute('GET', '/admin/requirement/(\d+)/', RequirementController::class, 'editPage');
$app->addRoute('PUT', '/admin/requirement/(\d+)/', RequirementController::class, 'update');
$app->addRoute('DELETE', '/admin/requirement/(\d+)/', RequirementController::class, 'delete');
// Brand editing
$app->addRoute('GET', '/admin/brands/', BrandController::class, 'index');
// Brand CRUD
$app->addRoute('GET', '/admin/brands/(\d+)/', BrandController::class, 'editPage');
// Explorer
$app->addRoute('GET', '/admin/explorer/', ExplorerController::class, 'index');
// Folder CRUD
$app->addRoute('POST', '/admin/explorer/folders/', ExplorerController::class, 'folderCreate');
$app->addRoute('GET', '/admin/explorer/folders/', ExplorerController::class, 'folders');
$app->addRoute('PUT', '/admin/explorer/folders/', ExplorerController::class, 'folderRename');
$app->addRoute('DELETE', '/admin/explorer/folders/', ExplorerController::class, 'folderDelete');
// List files
$app->addRoute('GET', '/admin/explorer/upload/', ExplorerController::class, 'fileUploadDialog');
$app->addRoute('GET', '/admin/explorer/files/', ExplorerController::class, 'files');
$app->addRoute('GET', '/admin/explorer/search/', ExplorerController::class, 'search');
$app->addRoute('GET', '/admin/explorer/move/(\d+)/', ExplorerController::class, 'fileMoveDialog');
$app->addRoute('GET', '/admin/explorer/files/exists/', ExplorerController::class, 'fileExists');
$app->addRoute('GET', '/admin/explorer/files/(\d+)/image/edit/', ExplorerController::class, 'imageEditWidget');
$app->addRoute('PUT', '/admin/explorer/files/(\d+)/description/', ExplorerController::class, 'fileDescription');
// Image CRU
$app->addRoute('POST', '/admin/explorer/files/(\d+)/image/', ExplorerController::class, 'imageSaveThumb');
$app->addRoute('GET', '/admin/explorer/files/(\d+)/image/', ExplorerController::class, 'image');
$app->addRoute('PUT', '/admin/explorer/files/(\d+)/image/', ExplorerController::class, 'imageSave');
// File CRUD
$app->addRoute('POST', '/admin/explorer/files/', ExplorerController::class, 'fileUpload');
$app->addRoute('GET', '/admin/explorer/files/(\d+)/', ExplorerController::class, 'fileView');
$app->addRoute('PUT', '/admin/explorer/files/(\d+)/', ExplorerController::class, 'fileRename');
$app->addRoute('DELETE', '/admin/explorer/files/(\d+)/', ExplorerController::class, 'fileDelete');
// Addressbook
$app->addRoute('GET', '/admin/addressbook/list/', AddressbookController::class, 'index');
$app->addRoute('GET', '/admin/addressbook/', AddressbookController::class, 'editContact');
// Addressbook CRUD
$app->addRoute('POST', '/admin/addressbook/', AddressbookController::class, 'create');
$app->addRoute('GET', '/admin/addressbook/(\d+)/', AddressbookController::class, 'editContact');
$app->addRoute('PUT', '/admin/addressbook/(\d+)/', AddressbookController::class, 'update');
$app->addRoute('DELETE', '/admin/addressbook/(\d+)/', AddressbookController::class, 'delete');
// Users
$app->addRoute('GET', '/admin/users/new/', UserController::class, 'newUser');
$app->addRoute('GET', '/admin/users/', UserController::class, 'index');
// User CRUD
$app->addRoute('POST', '/admin/users/new/', UserController::class, 'create');
$app->addRoute('GET', '/admin/users/(\d+)/', UserController::class, 'editUser');
$app->addRoute('PUT', '/admin/users/(\d+)/', UserController::class, 'update');
$app->addRoute('DELETE', '/admin/users/(\d+)/', UserController::class, 'delete');
// Maintenance
$app->addRoute('GET', '/admin/maintenance/', MaintenanceController::class, 'index');
$app->addRoute('DELETE', '/admin/maintenance/contacts/empty/', MaintenanceController::class, 'removeBadContacts');
$app->addRoute('GET', '/admin/maintenance/contacts/invalid/', MaintenanceController::class, 'contactsWithInvalidEmails');
$app->addRoute('GET', '/admin/maintenance/pages/mismatches/', MaintenanceController::class, 'mismatchedBindings');
$app->addRoute('GET', '/admin/maintenance/pages/orphans/', MaintenanceController::class, 'orphanPages');
$app->addRoute('GET', '/admin/maintenance/categories/circular/', MaintenanceController::class, 'circularLinks');
$app->addRoute('GET', '/admin/maintenance/files/names/', MaintenanceController::class, 'badFileNames');
$app->addRoute('GET', '/admin/maintenance/files/folderNames/', MaintenanceController::class, 'badFolderNames');
$app->addRoute('POST', '/admin/maintenance/emails/send/', MaintenanceController::class, 'sendDelayedEmail');
$app->addRoute('GET', '/admin/maintenance/emails/usage/', MaintenanceController::class, 'mailUsage');
$app->addRoute('GET', '/admin/maintenance/usage/', MaintenanceController::class, 'usage');
// Invoice
$app->addRoute('GET', '/admin/invoices/', InvoiceController::class, 'index');
$app->addRoute('GET', '/admin/invoices/payments/', InvoiceController::class, 'validationList');
$app->addRoute('PUT', '/admin/invoices/payments/(\d+)/', InvoiceController::class, 'validate');
$app->addRoute('GET', '/admin/invoices/(\d+)/pdf/', InvoiceController::class, 'pdf');
// Invoice CRU
$app->addRoute('POST', '/admin/invoices/', InvoiceController::class, 'create');
$app->addRoute('GET', '/admin/invoices/(\d+)/', InvoiceController::class, 'invoice');

$app->run(Request::createFromGlobals());
