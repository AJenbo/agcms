<?php

use AGCMS\Config;

function makeNewList(string $navn): array
{
    db()->query('INSERT INTO `tablesort` (`navn`) VALUES (\'' . db()->esc($navn) . '\')');

    return ['id' => db()->insert_id, 'name' => $navn];
}

function saveListOrder(int $id, string $navn, string $text): bool
{
    db()->query(
        'UPDATE `tablesort` SET navn = \'' . db()->esc($navn) . '\', text = \'' . db()->esc($text) . '\'
        WHERE id = ' . $id
    );

    return true;
}

function listsort()
{
    $data = [];
    Render::addLoadedTable('tablesort');
    $data['lists'] = db()->fetchArray('SELECT id, navn FROM `tablesort`');
}

function listsortEdit(Request $request)
{
    // listsort-edit
    $data = [];
    $id = (int) $request->get('id', 0);
    if ($id) {
        Render::addLoadedTable('tablesort');
        $list = db()->fetchOne('SELECT * FROM `tablesort` WHERE `id` = ' . $id);
        $data = [
            'id'        => $id,
            'name'      => $list['navn'],
            'rows'      => explode('<', $list['text']),
            'textWidth' => Config::get('text_width'),
        ] + $data;
    }
}
