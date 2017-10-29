<?php

use AGCMS\Config;
use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\ORM;
use AGCMS\Render;

/**
 * Print a Google sitemap.
 */
require_once __DIR__ . '/inc/Bootstrap.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('files');
Render::addLoadedTable('kat');
Render::addLoadedTable('sider');
Render::addLoadedTable('special');
Render::sendCacheHeader();

$page = ORM::getOne(CustomPage::class, 1);
assert($page instanceof CustomPage);
$urls = [
    [
        'loc' => Config::get('base_url') . '/',
        'lastmod' => $page->getTimeStamp(),
        'changefreq' => 'monthly',
        'priority' => '0.7',
    ],
    [
        'loc' => Config::get('base_url') . '/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=',
        'lastmod' => Render::getUpdateTime(false),
        'changefreq' => 'monthly',
        'priority' => '0.8',
    ],
];

$activeCategoryIds = [0];
$categories = ORM::getByQuery(Category::class, 'SELECT * FROM kat');
foreach ($categories as $category) {
    assert($category instanceof Category);
    if ($category->isInactive()) {
        continue;
    }
    $activeCategoryIds[] = $category->getId();

    $urls[] = [
        'loc' => Config::get('base_url') . $category->getCanonicalLink(),
        'changefreq' => 'weekly',
        'priority' => '0.5',
    ];
}
unset($categories, $category);

$brandIds = [];
$pages = ORM::getByQuery(
    Page::class,
    '
    SELECT sider.* FROM bind
    JOIN sider ON sider.id = bind.side
    WHERE bind.kat IN(' . implode(',', $activeCategoryIds) . ')
    '
);
unset($activeCategoryIds);
foreach ($pages as $page) {
    assert($page instanceof Page);
    $urls[] = [
        'loc' => Config::get('base_url') . $page->getCanonicalLink(),
        'lastmod' => $page->getTimeStamp(),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ];
}
unset($pages, $page);

if ($brandIds) {
    $brands = ORM::getByQuery(
        Brand::class,
        '
        SELECT * FROM maerke
        WHERE id IN(' . implode(',', array_keys($brandIds)) . ')
        '
    );
    foreach ($brands as $brand) {
        assert($brand instanceof Brand);
        $urls[] = [
            'loc' => Config::get('base_url') . $brand->getCanonicalLink(),
            'changefreq' => 'weekly',
            'priority' => '0.4',
        ];
    }
    unset($brands, $brand);
}
unset($brandIds);

$requirements = ORM::getByQuery(Requirement::class, 'SELECT * FROM krav');
foreach ($requirements as $requirement) {
    assert($requirement instanceof Requirement);
    $urls[] = [
        'loc' => Config::get('base_url') . $requirement->getCanonicalLink(),
        'changefreq' => 'monthly',
        'priority' => '0.2',
    ];
}
unset($requirements, $requirement);

header('Content-Type:text/xml;charset=utf-8');
Render::output('sitemap', ['urls' => $urls]);
