<?php

use AGCMS\Config;
use AGCMS\Entity\Page;
use AGCMS\ORM;
use AGCMS\Render;

/**
 * Print feed for pricerunner.com.
 */
require_once __DIR__ . '/inc/Bootstrap.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('files');
Render::addLoadedTable('kat');
Render::addLoadedTable('maerke');
Render::addLoadedTable('sider');
Render::sendCacheHeader();

$products = [];

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

    $categories = [];
    $brand = $page->getBrand();
    if ($brand) {
        $brand = $brand->getTitle();
        $categories[] = $brand;
    }
    $product = [
        'sku' => $page->getId(),
        'title' => $page->getTitle(),
        'price' => $page->getPrice() . ',00',
        'imageUrl' => Config::get('base_url') . encodeUrl($page->getImagePath()),
        'link' => Config::get('base_url') . encodeUrl($page->getCanonicalLink()),
        'manufacture' => $brand,
        'manufactureSku' => $page->getSku(),
    ];
    $product = array_map('trim', $product);

    foreach ($page->getCategories() as $category) {
        do {
            $categories[] = $category->getTitle();
        } while ($category = $category->getParent());
    }

    $categories = array_map('trim', $categories);
    $categories = array_filter($categories);
    $categories = array_unique(array_reverse($categories));
    $product['categories'] = $categories;

    $products[] = $product;
}

header('Content-Type: application/xml');
Render::output('pricerunner', compact('products'));
