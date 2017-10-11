<?php

use AGCMS\Config;
use AGCMS\Entity\Category;
use AGCMS\Entity\Contact;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Page;
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
        'kat_expand'                        => ['method' => 'GET'],
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
        $page = ORM::getOne(Page::class, $request->get('id', 0));
        $bindings = [];
        $accessories = [];
        if ($page) {
            /** @var Page */
            foreach ($page->getCategories() as $category) {
                $bindings[$category->getId()] = $category->getPath();
            }

            foreach ($page->getAccessories() as $accessory) {
                $category = $accessory->getPrimaryCategory();
                $accessories[$accessory->getId()] = $category->getPath() . $accessory->getTitle();
            }
        }

        $activeCategoryId = max($request->cookies->get('activekat', -1), -1);
        $data = [
            'textWidth' => Config::get('text_width'),
            'thumbWidth' => Config::get('thumb_width'),
            'input' => 'categories',
            'includePages' => false,
            'categoryPath' => $data['hide']['categories'] ? katspath($activeCategoryId)['html'] : 'Select location:',
            'activeCategoryId' => $activeCategoryId,
            'categories' => getCategoryRootStructure(),
            'requirementOptions' => getRequirementOptions(),
            'brandOptions' => getBrandOptions(),
            'page' => $page,
            'bindings' => $bindings,
            'accessories' => $accessories,
        ] + $data;
        break;
    case 'admin-getSiteTree':
        $customPages = ORM::getByQuery(CustomPage::class, "SELECT * FROM `special` WHERE `id` > 1 ORDER BY `navn`");
        $data = [
            'categories' => getCategoryRootStructure(true),
            'customPages' => $customPages,
            'includePages' => true,
            'input' => '',
        ] + $data;
        break;
    case 'admin-redigerkat':
        $activeCategoryId = max($request->cookies->get('activekat', -1), -1);
        $data = [
            'textWidth' => Config::get('text_width'),
            'emails' => array_keys(Config::get('emails')),
            'activeCategoryId' => $activeCategoryId,
            'input' => 'categories',
            'includePages' => false,
            'categoryPath' => $data['hide']['categories'] ? katspath($activeCategoryId)['html'] : 'Select location:',
            'categories' => getCategoryRootStructure(),
            'category' => ORM::getOne(Category::class, $request->get('id', 0)),
        ] + $data;
        break;
    case 'admin-krav':
        $data = [
            'requirements' => db()->fetchArray("SELECT id, navn title FROM `krav` ORDER BY navn"),
        ] + $data;
        break;
    case 'admin-editkrav':
        $requirement = ['id' => 0, 'html' => ''];
        $id = (int) $request->get('id', 0);
        if ($id) {
            $requirement = db()->fetchOne("SELECT id, navn title, text html FROM `krav` WHERE id = " . $id);
        }
        $data = [
            'textWidth' => Config::get('text_width'),
            'requirement' => $requirement,
        ] + $data;
        break;
    case 'admin-maerker':
        $data['brands'] = db()->fetchArray("SELECT id, navn title, ico icon, link FROM `maerke` ORDER BY navn");
        break;
    case 'admin-search':
        $data['text'] = $request->get('text');
        $data['pages'] = findPages($data['text']);
        break;
    case 'admin-updatemaerke':
        $id = (int) $request->get('id', 0);
        $data['brand'] = db()->fetchOne("SELECT id, navn title, link, ico icon FROM `maerke` WHERE id = " . $id);
        break;
    case 'admin-emaillist':
        $data['newsletters'] = db()->fetchArray(
            "SELECT id, subject, sendt sent FROM newsmails ORDER BY sendt, id DESC"
        );
        break;
    case 'admin-viewemail':
        $id = (int) $request->get('id', 0);
        $data['newsletter'] = ['id' => 0, 'html' => '', 'interests' => []];
        $data['recipientCount'] = 0;
        if ($id) {
            $data['newsletter'] = db()->fetchOne(
                "SELECT id, sendt sent, `from`, interests, subject, text html FROM newsmails WHERE id = " . $id
            );
            $data['newsletter']['interests'] = explode('<', $data['newsletter']['interests']);
        }
        $data['recipientCount'] = countEmailTo($data['newsletter']['interests']);
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
        $id = (int) $request->get('id', 0);
        $data['contact'] = ['id' => 0, 'interests' => []];
        if ($id) {
            $data['contact'] = db()->fetchOne(
                "
                SELECT
                    id,
                    interests,
                    navn name,
                    tlf1 phone1,
                    tlf2 phone2,
                    email,
                    adresse address,
                    land country,
                    post postcode,
                    `by` city,
                    kartotek newsletter
                FROM `email`
                WHERE `id` = " . $id
            );
            $data['contact']['interests'] = explode('<', $data['contact']['interests']);
        }
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
        $page = ORM::getOne(CustomPage::class, $request->get('id', 0));
        if ($page->getId() === 1) {
            $data['textWidth'] = Config::get('text_width');
            $data['categories'] = db()->fetchArray(
                "SELECT id, navn title, icon FROM `kat` WHERE bind = 0 ORDER BY `order`, `navn`"
            );
        }
        $data['page'] = ORM::getOne(CustomPage::class, $request->get('id', 0));
        $data['pageWidth'] = $page->getId() === 1 ? Config::get('frontpage_width') : Config::get('text_width');
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
