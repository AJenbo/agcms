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

$search = array (
    '@<script[^>]*?>.*?</script>@si', // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
    '@([\r\n])[\s]+@',                // Strip out white space
    '@&(&|#197);@i'
);

$replace = array (
    ' ',
    ' ',
    '\1',
    ' '
);

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
    <managingEditor>' . $GLOBALS['_config']['email'][0] . ' ('
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

$sider = db()->fetchArray(
    "
    SELECT sider.id,
        sider.maerke,
        sider.navn,
        sider.text,
        UNIX_TIMESTAMP(dato) AS dato,
        billed,
        kat.id AS kat_id
    FROM sider
    JOIN bind ON (side = sider.id)
    JOIN kat ON (kat.id = kat)
    " . $where . "
    GROUP BY id
    ORDER BY - dato" . $limit
);
foreach ($sider as $page) {
    $category = ORM::getOne(Category::class, $page['kat_id']);
    if ($category->isInactive()) {
        continue;
    }

    htmlspecialchars($page['navn'], ENT_COMPAT | ENT_XML1);
    if (!$page['navn'] = trim($name)) {
        $page['navn'] = $GLOBALS['_config']['site_name'];
    }

    echo '
    <item>
    <title>'.$page['navn'].'</title>
    <link>' . $GLOBALS['_config']['base_url'] . '/' . $category->getSlug(true)
    . 'side' . $page['id'] . '-' . rawurlencode(clearFileName($page['navn']))
    . '.html</link>
    <description>';
    if ($page['billed']
        && $page['billed'] != '/images/web/intet-foto.jpg'
    ) {
        echo '&lt;img style="float:left;margin:0 10px 5px 0;" src="'
        . $GLOBALS['_config']['base_url'] . $page['billed'] . '" &gt;&lt;p&gt;';
        //TODO limit to summery
    }

    $cleaned = trim(preg_replace($search, $replace, $page['text']));
    echo htmlspecialchars($cleaned, ENT_COMPAT | ENT_XML1) . '</description>
    <pubDate>' . gmdate('D, d M Y H:i:s', $page['dato']) . ' GMT</pubDate>
    <guid>' . $GLOBALS['_config']['base_url'] . '/' . $category->getSlug(true)
    . 'side' . $page['id'] . '-' . rawurlencode(clearFileName($page['navn']))
    . '.html</guid>';

    $categories = ORM::getByQuery(
        Category::class,
        "
        SELECT kat.*
        FROM `bind`
        JOIN kat ON kat.id = bind.kat
        WHERE bind.side = " . $page['id']
    );

    $categoryIds = [];
    foreach ($categories as $category) {
        do {
            $categoryIds[] = $category->getId();
        } while ($category = $category->getParent());
    }
    $categoryIds = array_unique($categoryIds);

    foreach ($categoryIds as $categoryId) {
        $category = ORM::getOne(Category::class, $categoryId);
        $cleaned = trim(preg_replace($search, $replace, $category->getTitle()));
        if ($cleaned) {
            echo '<category>';
            echo htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
            echo '</category>';
        }
    }
    if ($page['maerke']) {
        $maerker = db()->fetchOne(
            "
            SELECT `navn`
            FROM maerke
            WHERE id = " . $page['maerke']
        );
        $cleaned = preg_replace($search, $replace, $maerker['navn']);
        $cleaned = trim($cleanName);
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
