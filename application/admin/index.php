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

require_once __DIR__ . '/logon.php';

$request = request();
$template = 'admin-' . $request->get('side', 'index');

$data = getBasicAdminTemplateData();

switch ($template) {
    case 'admin-redigerkat':
        $id = $request->get('id');
        $selectedId = $request->cookies->get('activekat', -1);
        $category = null;
        if (null !== $id) {
            $category = ORM::getOne(Category::class, $id);
            if ($category) {
                assert($category instanceof Category);
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
        $data['requirements'] = ORM::getByQuery(Requirement::class, 'SELECT * FROM `krav` ORDER BY navn');
        break;
    case 'admin-editkrav':
        $data['textWidth'] = Config::get('text_width');
        $data['requirement'] = ORM::getOne(Requirement::class, $request->get('id', 0));
        break;
    case 'admin-maerker':
        $data['brands'] = ORM::getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn');
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
            'SELECT id, subject, sendt sent FROM newsmails ORDER BY sendt, id DESC'
        );
        break;
    case 'admin-viewemail':
        $id = (int) $request->get('id', 0);
        $data['recipientCount'] = 0;
        if ($id) {
            $data['newsletter'] = db()->fetchOne(
                'SELECT id, sendt sent, `from`, interests, subject, text html FROM newsmails WHERE id = ' . $id
            );
            $data['newsletter']['interests'] = explode('<', $data['newsletter']['interests']);
        }
        $data['recipientCount'] = countEmailTo($data['newsletter']['interests'] ?? []);
        $data['interests'] = Config::get('interests', []);
        $data['textWidth'] = Config::get('text_width');
        $data['emails'] = array_keys(Config::get('emails'));
        break;
    case 'admin-addressbook':
        $order = $request->get('order');
        if (!in_array($order, ['email', 'tlf1', 'tlf2', 'post', 'adresse'], true)) {
            $order = 'navn';
        }
        $data['contacts'] = ORM::getByQuery(Contact::class, 'SELECT * FROM email ORDER BY ' . $order);
        break;
    case 'admin-editContact':
        $data['contact'] = ORM::getOne(Contact::class, $request->get('id', 0));
        $data['interests'] = Config::get('interests', []);
        break;
    case 'admin-get_db_error':
        $emails = db()->fetchArray("SHOW TABLE STATUS LIKE 'emails'");
        $emails = reset($emails);
        $page = ORM::getOne(CustomPage::class, 0);
        assert($page instanceof CustomPage);
        $data = [
            'dbSize' => get_db_size(),
            'wwwSize' => get_size_of_files(),
            'pendingEmails' => db()->fetchOne("SELECT count(*) as 'count' FROM `emails`")['count'],
            'totalDelayedEmails' => $emails['Auto_increment'] - 1,
            'lastrun' => $page->getTimestamp(),
        ] + $data;
        break;
    case 'admin-redigerSpecial':
        $data['page'] = ORM::getOne(CustomPage::class, $request->get('id', 0));
        $data['pageWidth'] = Config::get('text_width');
        if (1 === $data['page']->getId()) {
            $category = ORM::getOne(Category::class, 0);
            assert($category instanceof Category);
            $data['category'] = $category;
            $data['textWidth'] = Config::get('text_width');
            $data['pageWidth'] = Config::get('frontpage_width');
            $data['categories'] = $category->getChildren();
        }
        break;

    case 'admin-listsort':
        $data['lists'] = db()->fetchArray('SELECT id, navn FROM `tablesort`');
        break;
    case 'admin-listsort-edit':
        $id = (int) $request->get('id', 0);
        if ($id) {
            $list = db()->fetchOne('SELECT * FROM `tablesort` WHERE `id` = ' . $id);
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
