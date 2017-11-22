<?php

$_SERVER['SCRIPT_FILENAME'] = '/home/ajenbo/code/ArmsGallery/application/index.php';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_FILENAME'];
include __DIR__ . '/../index.php';

use AGCMS\Config;

switch ('') {
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
    case 'admin-listsort':
        $data['lists'] = db()->fetchArray('SELECT id, navn FROM `tablesort`');
        break;
    case 'admin-listsort-edit':
        $id = (int) $request->get('id', 0);
        if ($id) {
            $list = db()->fetchOne('SELECT * FROM `tablesort` WHERE `id` = ' . $id);
            $data = [
                'id'        => $id,
                'name'      => $list['navn'],
                'rows'      => explode('<', $list['text']),
                'textWidth' => Config::get('text_width'),
            ] + $data;
        }
        break;
}
