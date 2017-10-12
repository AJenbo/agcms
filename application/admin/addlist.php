<?php

use AGCMS\Entity\Table;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

$request = request();
if ($request->isMethod('POST')) {
    //TODO Send JSON directly from the client
    $columnSortings = explode('<', $request->get('sorts'));
    $columnSortings = array_map('intval', $columnSortings);
    $columnTypes = explode('<', $request->get('cells'));
    $columnTypes = array_map('intval', $columnTypes);
    $columnTitles = explode('<', $request->get('cell_names'));
    $columnTitles = array_map('html_entity_decode', $columnTitles);

    $columns = [];
    foreach ($columnTitles as $key => $title) {
        $columns[] = [
            'title'   => $title,
            'type'    => $columnTypes[$key] ?? 0,
            'sorting' => $columnSortings[$key] ?? 0,
        ];
    }

    $table = new Table([
        'page_id'     => $request->get('id'),
        'title'       => $request->get('title'),
        'column_data' => json_encode($columns),
        'order_by'    => $request->get('dsort'),
        'has_links'   => (bool) $request->get('link'),
    ]);
    $table->save();

    Render::output('admin-addlist-response');

    return;
}

$data = [
    'tablesorts' => db()->fetchArray('SELECT id, navn title FROM `tablesort`'),
    'id'         => (int) $request->get('id'),
];
Render::output('admin-addlist', $data);
