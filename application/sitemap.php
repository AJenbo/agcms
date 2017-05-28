<?php

use AGCMS\Render;

/**
 * Print a Google sitemap
 */

require_once __DIR__ . '/inc/Bootstrap.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('files');
Render::addLoadedTable('kat');
Render::addLoadedTable('sider');
Render::addLoadedTable('special');
Render::sendCacheHeader();
header('Content-Type:text/xml;charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8" ?>';

echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
    . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
    . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
    . ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"><url><loc>'
    . Config::get('base_url')
    . '/</loc><lastmod>' .  date('c', ORM::getOne(CustomPage::class, 1)->getTimeStamp())
    . '</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url><url><loc>'
    . Config::get('base_url')
    . '/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=</loc><lastmod>'
    . date('c', Render::getUpdateTime(false))
    . '</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>';

$activeCategoryIds = [0];
$categories = ORM::getByQuery(Category::class, "SELECT * FROM kat");
foreach ($categories as $category) {
    if ($category->isInactive()) {
        continue;
    }
    $activeCategoryIds[] = $category->getId();

    echo '<url><loc>' . htmlspecialchars(Config::get('base_url') . $category->getCanonicalLink(), ENT_COMPAT | ENT_XML1)
        . '</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>';
}
unset($categories, $category);

$brandIds = [];
$pages = ORM::getByQuery(
    Page::class,
    "
    SELECT sider.* FROM bind
    JOIN sider ON sider.id = bind.side
    WHERE bind.kat IN(" . implode(",", $activeCategoryIds) . ")
    "
);
unset($activeCategoryIds);
foreach ($pages as $page) {
    $brandIds[$page->getBrandId()] = true;
    echo '<url><loc>' . htmlspecialchars(Config::get('base_url') . $page->getCanonicalLink(), ENT_COMPAT | ENT_XML1)
        . '</loc><lastmod>' . htmlspecialchars(date('c', $page->getTimeStamp()), ENT_COMPAT | ENT_XML1)
        . '</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>';
}
unset($pages, $page);

if ($brandIds) {
    $brands = ORM::getByQuery(
        Brand::class,
        "
        SELECT * FROM maerke
        WHERE id IN(" . implode(",", array_keys($brandIds)) . ")
        "
    );
    foreach ($brands as $brand) {
        echo '<url><loc>' . htmlspecialchars(Config::get('base_url')
            . $brand->getCanonicalLink(), ENT_COMPAT | ENT_XML1)
            . '</loc><changefreq>weekly</changefreq><priority>0.4</priority></url>';
    }
    unset($brands, $brand);
}
unset($brandIds);

$requirements = ORM::getByQuery(Requirement::class, "SELECT * FROM krav");
foreach ($requirements as $requirement) {
    echo '<url><loc>' . htmlspecialchars(Config::get('base_url')
        . $requirement->getCanonicalLink(), ENT_COMPAT | ENT_XML1)
        . '</loc><changefreq>monthly</changefreq><priority>0.2</priority></url>';
}
unset($requirements, $requirement);

?></urlset>
