<?php

use AGCMS\Config;
use AGCMS\Entity\Page;
use AGCMS\ORM;
use AGCMS\Render;

/**
 * Print RSS feed contaning the 20 last changed pages.
 */
require_once __DIR__ . '/inc/Bootstrap.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('files');
Render::addLoadedTable('kat');
Render::addLoadedTable('maerke');
Render::addLoadedTable('sider');
$timestamp = Render::getUpdateTime();
Render::sendCacheHeader($timestamp);

$data = [
    'url' => Config::get('base_url') . '/rss.php',
    'title' => Config::get('site_name'),
    'siteUrl' => Config::get('base_url') . '/',
    'lastBuildDate' => gmdate('D, d M Y H:i:s', $timestamp) . ' GMT',
    'email' => first(Config::get('emails'))['address'],
    'siteName' => Config::get('site_name'),
    'items' => [],
];

$time = 0;
if (request()->headers->has('If-Modified-Since')) {
    $time = strtotime(stripslashes(request()->headers->get('If-Modified-Since')));
}

$limit = '';
$where = '';
if ($time) {
    $where = " WHERE `dato` > '" . date('Y-m-d h:i:s', $time) . "'";
} else {
    $limit = ' LIMIT 20';
}

$pages = ORM::getByQuery(
    Page::class,
    'SELECT * FROM sider'
    . $where
    . ' ORDER BY dato DESC'
    . $limit
);
foreach ($pages as $page) {
    assert($page instanceof Page);
    if ($page->isInactive()) {
        continue;
    }

    $decription = '';
    if ($page->getIcon() && '/images/web/intet-foto.jpg' !== $page->getIcon()->getPath()) {
        $decription .= '<img style="float:left;margin:0 10px 5px 0;" src="'
            . Config::get('base_url') . encodeUrl($page->getIcon()->getPath()) . '" ><p>';
    }
    $decription .= $page->getExcerpt() . '</p>';

    $categories = [];
    foreach ($page->getCategories() as $category) {
        do {
            $categories[] = $category->getTitle();
        } while ($category = $category->getParent());
    }
    $brand = $page->getBrand();
    if ($brand) {
        $categories[] = $brand->getTitle();
    }
    $categories = array_map('trim', $categories);
    $categories = array_filter($categories);
    $categories = array_unique($categories);

    $data['items'][] = [
        'title' => trim($page->getTitle()) ?: Config::get('site_name'),
        'link' => Config::get('base_url') . encodeUrl($page->getCanonicalLink()),
        'description' => $decription,
        'pubDate' => gmdate('D, d M Y H:i:s', $page->getTimeStamp()) . ' GMT',
        'categories' => $categories,
    ];
}

header('Content-Type: application/rss+xml');
Render::output('rss', $data);
