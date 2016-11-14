<?php
/**
 * Print RSS feed contaning the 20 last changed pages
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('files');
Render::addLoadedTable('kat');
Render::addLoadedTable('maerke');
Render::addLoadedTable('sider');
$timestamp = Render::getUpdateTime();
Render::sendCacheHeader($timestamp);

header('Content-Type: application/rss+xml');

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

$email = first(Config::get('emails'))['address'];
echo '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <atom:link href="' . Config::get('base_url')
    . '/rss.php" rel="self" type="application/rss+xml" />

    <title>' . Config::get('site_name') . '</title>
    <link>' . Config::get('base_url') . '/</link>
    <description>De nyeste sider</description>
    <language>da</language>
    <lastBuildDate>' . gmdate('D, d M Y H:i:s', $timestamp)
    . ' GMT</lastBuildDate>
    <managingEditor>' . $email . ' ('
    . Config::get('site_name') . ')</managingEditor>';

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

    $title = trim($page->getTitle());
    if (!$title) {
        $title = Config::get('site_name');
    }
    echo '<item><title>' . htmlspecialchars($title, ENT_COMPAT | ENT_XML1) . '</title><link>'
    . Config::get('base_url') . encodeUrl($page->getCanonicalLink()) . '</link><description>';
    if ($page->getImagePath() && $page->getImagePath() !== '/images/web/intet-foto.jpg') {
        echo '&lt;img style="float:left;margin:0 10px 5px 0;" src="'
        . Config::get('base_url') . encodeUrl($page->getImagePath()) . '" &gt;&lt;p&gt;';
    }

    $cleaned = trim(preg_replace($search, $replace, $page->getExcerpt()));
    echo htmlspecialchars($cleaned, ENT_COMPAT | ENT_XML1) . '</description><pubDate>'
    . gmdate('D, d M Y H:i:s', $page->getTimeStamp()) . ' GMT</pubDate><guid>'
    . Config::get('base_url') . encodeUrl($page->getCanonicalLink()) . '</guid>';

    $categoryIds = [];
    foreach ($page->getCategories() as $category) {
        do {
            $categoryIds[] = $category->getId();
        } while ($category = $category->getParent());
    }
    foreach (array_unique($categoryIds) as $categoryId) {
        $category = ORM::getOne(Category::class, $categoryId);
        $cleaned = trim(preg_replace($search, $replace, $category->getTitle()));
        if ($cleaned) {
            echo '<category>' . htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1) . '</category>';
        }
    }
    $brand = $page->getBrand();
    if ($brand) {
        $cleaned = trim(preg_replace($search, $replace, $brand->getTitle()));
        if ($cleaned) {
            echo '<category>' . htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1) . '</category>';
        }
    }

    echo '</item>';
}
db()->close();
echo '</channel></rss>';
