<?php

use AGCMS\Render;
use AGCMS\ORM;
use AGCMS\Config;
use AGCMS\Entity\Page;
use AGCMS\Entity\Category;

/**
 * Print RSS feed contaning the 20 last changed pages
 */

require_once __DIR__ . '/inc/Bootstrap.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('files');
Render::addLoadedTable('kat');
Render::addLoadedTable('maerke');
Render::addLoadedTable('sider');
$timestamp = Render::getUpdateTime();
Render::sendCacheHeader($timestamp);

$search = [
    '@<script[^>]*?>.*?</script>@siu', // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@sui',          // Strip out HTML tags
    '@([\r\n])[\s]+@u',                // Strip out white space
    '@&(&|#197);@iu'
];

$replace = [
    ' ',
    ' ',
    '\1',
    ' '
];

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
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $time = strtotime(stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']));
}

$limit = '';
$where = '';
if ($time > 1000000000) {
    $where = " WHERE `dato` > '" . date('Y-m-d h:i:s', $time) . "'";
} else {
    $limit = " LIMIT 20";
}

$pages = ORM::getByQuery(
    Page::class,
    "SELECT * FROM sider"
    . $where
    . " ORDER BY dato DESC"
    . $limit
);
foreach ($pages as $page) {
    if ($page->isInactive()) {
        continue;
    }

    $decription = '';
    if ($page->getImagePath() && $page->getImagePath() !== '/images/web/intet-foto.jpg') {
        $decription .= '<img style="float:left;margin:0 10px 5px 0;" src="'
            . Config::get('base_url') . encodeUrl($page->getImagePath()) . '" ><p>';
    }
    $decription .= trim(preg_replace($search, $replace, $page->getExcerpt())) . '</p>';

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
