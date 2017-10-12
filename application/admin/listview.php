<?php

use AGCMS\Entity\Category;
use AGCMS\ORM;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('kat');
Render::addLoadedTable('krav');
Render::addLoadedTable('maerke');
Render::addLoadedTable('sider');
Render::sendCacheHeader();

$sort = request()->get('sort', 'navn');
$reverseOrder = false;
if ('-' === mb_substr($sort, 0, 1)) {
    $sort = mb_substr($sort, 1);
    $reverseOrder = true;
}

$sortOptions = [
    'id'     => 'ID',
    'navn'   => 'Navn',
    'varenr' => 'Varenummer',
    'for'    => 'Før pris',
    'pris'   => 'Nu Pris',
    'dato'   => 'Sidst ændret',
    'maerke' => 'Mærke',
    'krav'   => 'Krav',
];

$sort = isset($sortOptions[$sort]) ? $sort : 'navn';

$categoryId = request()->get('kat', '');
if ('' === $categoryId) {
    $categories = ORM::getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL');
} else {
    $categories = [ORM::getOne(Category::class, $categoryId)];
}

Render::output(
    'admin-listview',
    [
        'sortOptions'  => $sortOptions,
        'sort'         => $sort,
        'reverseOrder' => $reverseOrder,
        'requirements' => getRequirementOptions(),
        'brands'       => getBrandOptions(),
        'categories'   => $categories,
        'pathPrefix'   => '',
        'categoryId'   => $categoryId,
    ]
);
