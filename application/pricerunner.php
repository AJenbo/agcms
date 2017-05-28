<?php

use AGCMS\Render;

/**
 * Print feed for pricerunner.com
 */

require_once __DIR__ . '/inc/Bootstrap.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('files');
Render::addLoadedTable('kat');
Render::addLoadedTable('maerke');
Render::addLoadedTable('sider');
Render::sendCacheHeader();

header('Content-Type: application/xml');
echo '<?xml version="1.0" encoding="utf-8"?><products>';

$search = [
    '@<script[^>]*?>.*?</script>@si', // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
    '@([\r\n])[\s]+@',                // Strip out white space
    '@&(&|#197);@i'
];

$replace = [
    ' ',
    ' ',
    '\1',
    ' '
];

$pages = ORM::getByQuery(
    Page::class,
    "
    SELECT * FROM sider
    WHERE `pris` > 0 AND `navn` != ''
    ORDER BY `varenr` DESC
    "
);
foreach ($pages as $page) {
    if ($page->isInactive()) {
        continue;
    }

    echo '<product><sku>' . $page->getId() . '</sku><title>'
    . htmlspecialchars(trim($page->getTitle()), ENT_COMPAT | ENT_XML1) . '</title>';
    if (trim($page->getSku())) {
        echo '<companysku>' . htmlspecialchars(trim($page->getSku()), ENT_COMPAT | ENT_XML1) . '</companysku>';
    }
    echo '<price>' . $page->getPrice() . ',00</price><img>'
    . Config::get('base_url') . encodeUrl($page->getImagePath()) . '</img><link>'
    . Config::get('base_url') . encodeUrl($page->getCanonicalLink()) . '</link>';

    $categoryTitles = [];
    $brand = $page->getBrand();
    if ($brand) {
        $cleaned = trim(preg_replace($search, $replace, $brand->getTitle()));
        if ($cleaned) {
            $categoryTitles[] = htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
        }
        echo '<company>';
        echo htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
        echo '</company>';
    }

    $categoryIds = [];
    foreach ($page->getCategories() as $category) {
        do {
            $categoryIds[] = $category->getId();
        } while ($category = $category->getParent());
    }
    foreach (array_unique($categoryIds) as $categoryId) {
        $category = ORM::getOne(Category::class, $categoryId);
        $cleaned = preg_replace($search, $replace, $category->getTitle());
        $cleaned = trim($cleaned);
        if ($cleaned) {
            $categoryTitles[] = htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
        }
    }

    $categoryTitles = array_unique(array_reverse($categoryTitles));

    echo '<category>' . implode(' &gt; ', $categoryTitles) . '</category>';
    echo '</product>';
}
echo '</products>';
