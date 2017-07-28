<?php

use AGCMS\Entity\Table;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

if (!empty($_POST)) {
    //TODO Send JSON directly from the client
    $columnSortings = explode('<', $_POST['sorts']);
    $columnSortings = array_map('intval', $columnSortings);
    $columnTypes = explode('<', $_POST['cells']);
    $columnTypes = array_map('intval', $columnTypes);
    $columnTitles = explode('<', $_POST['cell_names']);
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
        'page_id'     => $_POST['id'],
        'title'       => $_POST['title'],
        'column_data' => json_encode($columns),
        'order_by'    => $_POST['dsort'],
        'has_links'   => (bool) $_POST['link'],
    ]);
    $table->save();

    echo Render::render('admin-addlist-response');

    return;
}

$data = [
    'tablesorts' => db()->fetchArray("SELECT id, navn title FROM `tablesort`"),
    'id' => (int) ($_GET['id'] ?? 0),
];

echo Render::render('admin-addlist', $data);
