<?php

use AGCMS\Config;
use AGCMS\Entity\Category;
use AGCMS\Entity\Contact;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Brand;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\ORM;
use AGCMS\Render;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';

Sajax::export(
    [
        'addAccessory'                      => ['method' => 'POST'],
        'bind'                              => ['method' => 'POST'],
        'check_file_names'                  => ['method' => 'GET', 'asynchronous' => false],
        'check_file_paths'                  => ['method' => 'GET', 'asynchronous' => false],
        'countEmailTo'                      => ['method' => 'GET'],
        'deleteContact'                     => ['method' => 'POST'],
        'get_db_size'                       => ['method' => 'GET', 'asynchronous' => false],
        'get_looping_cats'                  => ['method' => 'GET', 'asynchronous' => false],
        'get_mail_size'                     => ['method' => 'GET'],
        'get_orphan_pages'                  => ['method' => 'GET', 'asynchronous' => false],
        'get_pages_with_mismatch_bindings'  => ['method' => 'GET', 'asynchronous' => false],
        'get_size_of_files'                 => ['method' => 'GET', 'asynchronous' => false],
        'get_subscriptions_with_bad_emails' => ['method' => 'GET', 'asynchronous' => false],
        'expandCategory'                    => ['method' => 'GET'],
        'katspath'                          => ['method' => 'GET'],
        'listRemoveRow'                     => ['method' => 'POST'],
        'listSavetRow'                      => ['method' => 'POST'],
        'makeNewList'                       => ['method' => 'POST'],
        'movekat'                           => ['method' => 'POST'],
        'opretSide'                         => ['method' => 'POST'],
        'optimizeTables'                    => ['method' => 'POST', 'asynchronous' => false],
        'removeAccessory'                   => ['method' => 'POST'],
        'removeBadSubmisions'               => ['method' => 'POST', 'asynchronous' => false],
        'removeNoneExistingFiles'           => ['method' => 'POST', 'asynchronous' => false],
        'renamekat'                         => ['method' => 'POST'],
        'saveEmail'                         => ['method' => 'POST'],
        'savekrav'                          => ['method' => 'POST'],
        'saveListOrder'                     => ['method' => 'POST'],
        'save_ny_kat'                       => ['method' => 'POST'],
        'search'                            => ['method' => 'GET'],
        'sendDelayedEmail'                  => ['method' => 'POST', 'asynchronous' => false],
        'sendEmail'                         => ['method' => 'POST'],
        'sletbind'                          => ['method' => 'POST'],
        'sletkat'                           => ['method' => 'POST'],
        'sletkrav'                          => ['method' => 'POST'],
        'sletmaerke'                        => ['method' => 'POST'],
        'sletSide'                          => ['method' => 'POST'],
        'sogogerstat'                       => ['method' => 'POST'],
        'updateContact'                     => ['method' => 'POST'],
        'updateKat'                         => ['method' => 'POST'],
        'updateKatOrder'                    => ['method' => 'POST', 'asynchronous' => false],
        'updatemaerke'                      => ['method' => 'POST'],
        'updateSide'                        => ['method' => 'POST'],
        'updateSpecial'                     => ['method' => 'POST'],
    ]
);
Sajax::handleClientRequest();

$request = request();
$template = 'admin-' . $request->get('side', 'index');

$data = getBasicAdminTemplateData();

switch ($template) {
    case 'admin-redigerside':
        $id = $request->get('id');
        $selectedId = $request->cookies->get('activekat', -1);
        $page = null;
        $bindings = [];
        $accessories = [];
        if ($id !== null) {
            /** @var Page */
            $page = ORM::getOne(Page::class, $id);
            if ($page) {
                foreach ($page->getCategories() as $category) {
                    $bindings[$category->getId()] = $category->getPath();
                }

                foreach ($page->getAccessories() as $accessory) {
                    $category = $accessory->getPrimaryCategory();
                    $accessories[$accessory->getId()] = $category->getPath() . $accessory->getTitle();
                }
            }
        }

        $data = [
            'textWidth' => Config::get('text_width'),
            'thumbWidth' => Config::get('thumb_width'),
            'siteTree' => getSiteTreeData('categories', $selectedId),
            'requirementOptions' => getRequirementOptions(),
            'brandOptions' => getBrandOptions(),
            'page' => $page,
            'bindings' => $bindings,
            'accessories' => $accessories,
        ] + $data;
        break;
    case 'admin-getSiteTree':
        $data['siteTree'] = getSiteTreeData();
        break;
    case 'admin-redigerkat':
        $id = $request->get('id');
        $selectedId = $request->cookies->get('activekat', -1);
        $category = null;
        if ($id !== null) {
            $category = ORM::getOne(Category::class, $id);
            if ($category) {
                $selectedId = $category->getParent() ? $category->getParent()->getId() : null;
            }
        }

        $data = [
            'textWidth' => Config::get('text_width'),
            'emails' => array_keys(Config::get('emails')),
            'siteTree' => getSiteTreeData('categories', $selectedId),
            'includePages' => false,
            'category' => $category,
        ] + $data;
        break;
    case 'admin-krav':
        $data['requirements'] = ORM::getByQuery(Requirement::class, "SELECT * FROM `krav` ORDER BY navn");
        break;
    case 'admin-editkrav':
        $data['textWidth'] = Config::get('text_width');
        $data['requirement'] = ORM::getOne(Requirement::class, $request->get('id', 0));
        break;
    case 'admin-maerker':
        $data['brands'] = ORM::getByQuery(Brand::class, "SELECT * FROM `maerke` ORDER BY navn");
        break;
    case 'admin-search':
        $data['text'] = $request->get('text');
        $data['pages'] = findPages($data['text']);
        break;
    case 'admin-updatemaerke':
        $data['brand'] = ORM::getOne(Brand::class, $request->get('id', 0));
        break;
    case 'admin-emaillist':
        $data['newsletters'] = db()->fetchArray(
            "SELECT id, subject, sendt sent FROM newsmails ORDER BY sendt, id DESC"
        );
        break;
    case 'admin-viewemail':
        $id = (int) $request->get('id', 0);
        $data['recipientCount'] = 0;
        if ($id) {
            $data['newsletter'] = db()->fetchOne(
                "SELECT id, sendt sent, `from`, interests, subject, text html FROM newsmails WHERE id = " . $id
            );
            $data['newsletter']['interests'] = explode('<', $data['newsletter']['interests']);
        }
        $data['recipientCount'] = countEmailTo($data['newsletter']['interests'] ?? []);
        $data['interests'] = Config::get('interests', []);
        $data['textWidth'] = Config::get('text_width');
        break;
    case 'admin-addressbook':
        $order = $request->get('order');
        if (!in_array($order, ['email', 'tlf1', 'tlf2', 'post', 'adresse'], true)) {
            $order = 'navn';
        }
        $data['contacts'] = ORM::getByQuery(Contact::class, "SELECT * FROM email ORDER BY " . $order);
        break;
    case 'admin-editContact':
        $data['contact'] = ORM::getOne(Contact::class, $request->get('id', 0));
        $data['interests'] = Config::get('interests', []);
        break;
    case 'admin-get_db_error':
        $emails = db()->fetchArray("SHOW TABLE STATUS LIKE 'emails'");
        $emails = reset($emails);
        $data = [
            'dbSize' => get_db_size(),
            'wwwSize' => get_size_of_files(),
            'pendingEmails' => db()->fetchOne("SELECT count(*) as 'count' FROM `emails`")['count'],
            'totalDelayedEmails' => $emails['Auto_increment'] - 1,
            'lastrun' => ORM::getOne(CustomPage::class, 0)->getTimestamp(),
        ] + $data;
        break;
    case 'admin-redigerSpecial':
        $data['page'] = ORM::getOne(CustomPage::class, $request->get('id', 0));
        $data['pageWidth'] = Config::get('text_width');
        if ($data['page']->getId() === 1) {
            $data['pageWidth'] = Config::get('frontpage_width');
            $data['categories'] = ORM::getOne(Category::class, 0)->getChildren();
        }
        break;

    case 'admin-listsort':
        $data['lists'] = db()->fetchArray('SELECT id, navn FROM `tablesort`');
        break;
    case 'admin-listsort-edit':
        $id = (int) $request->get('id', 0);
        if ($id) {
            $list = db()->fetchOne("SELECT * FROM `tablesort` WHERE `id` = " . $id);
            $data = [
                'id' => $id,
                'name' => $list['navn'],
                'rows' => explode('<', $list['text']),
                'textWidth' => Config::get('text_width'),
            ] + $data;
        }
        break;
}

Render::output($template, $data);
