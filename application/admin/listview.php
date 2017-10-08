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
if (mb_substr($sort, 0, 1) === '-') {
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

$categoryId = is_numeric(request()->get('kat')) ? (int) request()->get('kat') : null;
if ($categoryId < 1) {
    $categories = getPricelistRootStructure($sort, $categoryId);
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
