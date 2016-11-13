<?php
/**
 * Print RSS feed contaning the 20 last changed pages
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

Cache::addLoadedTable('sider');
Cache::addLoadedTable('bind');
Cache::addLoadedTable('kat');
Cache::addLoadedTable('maerke');
Cache::addLoadedTable('bind');
Cache::addLoadedTable('files');
$timestamp = Cache::getUpdateTime();
doConditionalGet($timestamp);

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

$email = reset($GLOBALS['_config']['emails'])['address'];
echo '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <atom:link href="' . $GLOBALS['_config']['base_url']
    . '/rss.php" rel="self" type="application/rss+xml" />

    <title>'.$GLOBALS['_config']['site_name'].'</title>
    <link>'.$GLOBALS['_config']['base_url'].'/</link>
    <description>De nyeste sider</description>
    <language>da</language>
    <lastBuildDate>' . gmdate('D, d M Y H:i:s', $timestamp)
    . ' GMT</lastBuildDate>
    <managingEditor>' . $email . ' ('
    . $GLOBALS['_config']['site_name'] . ')</managingEditor>';

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
        $title = $GLOBALS['_config']['site_name'];
    }
    echo '<item><title>' . htmlspecialchars($title, ENT_COMPAT | ENT_XML1) . '</title><link>'
    . $GLOBALS['_config']['base_url'] . encodeUrl($page->getCanonicalLink()) . '</link><description>';
    if ($page->getImagePath() && $page->getImagePath() !== '/images/web/intet-foto.jpg') {
        echo '&lt;img style="float:left;margin:0 10px 5px 0;" src="'
        . $GLOBALS['_config']['base_url'] . encodeUrl($page->getImagePath()) . '" &gt;&lt;p&gt;';
    }

    $cleaned = trim(preg_replace($search, $replace, $page->getExcerpt()));
    echo htmlspecialchars($cleaned, ENT_COMPAT | ENT_XML1) . '</description><pubDate>'
    . gmdate('D, d M Y H:i:s', $page->getTimeStamp()) . ' GMT</pubDate><guid>'
    . $GLOBALS['_config']['base_url'] . encodeUrl($page->getCanonicalLink()) . '</guid>';

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
            echo '<category>';
            echo htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
            echo '</category>';
        }
    }
    if ($page->getBrandId()) {
        $maerker = db()->fetchOne(
            "
            SELECT `navn`
            FROM maerke
            WHERE id = " . $page->getBrandId()
        );
        $cleaned = preg_replace($search, $replace, $maerker['navn']);
        $cleaned = trim($cleaned);
        if ($cleaned) {
            echo '<category>';
            echo htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
            echo '</category>';
        }
    }

    echo '</item>';
}
db()->close();
echo '</channel></rss>';
