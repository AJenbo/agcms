<?php
/**
 * Print a Google sitemap
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

doConditionalGet(Cache::getUpdateTime());
header('Content-Type:text/xml;charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8" ?>';

$special = db()->fetchOne("SELECT dato FROM special WHERE id = 1");
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
    <url>
        <loc><?php
        echo $GLOBALS['_config']['base_url'];
?>/</loc>
        <lastmod><?php
        echo mb_substr($special['dato'], 0, -9, 'UTF-8');
?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php
        echo $GLOBALS['_config']['base_url'];
?>/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=</loc>
        <lastmod>2007-02-02</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
<?php

$activeCategoryIds = [];
$categories = ORM::getByQuery(Category::class, "SELECT * FROM kat WHERE bind != -1");
foreach ($categories as $category) {
    if ($category->isInactive()) {
        continue;
    }
    $activeCategoryIds[] = $category->getId();

    //print xml
    ?><url><loc><?php
    echo htmlspecialchars(
        $GLOBALS['_config']['base_url'] . '/' . $category->getSlug(),
        ENT_COMPAT | ENT_XML1
    );
    ?></loc><changefreq>weekly</changefreq><priority>0.5</priority></url><?php
}

if ($activeCategoryIds) {
    $pages = ORM::getByQuery(
        Page::class,
        "
        SELECT sider.* FROM bind
        JOIN sider ON sider.id = bind.side
        WHERE bind.kat IN(" . implode(",", $activeCategoryIds) . ")
        "
    );
    foreach ($pages as $page) {
        //print xml
        ?><url><loc><?php
        echo htmlspecialchars(
            $GLOBALS['_config']['base_url'] . $page->getCanonicalLink(),
            ENT_COMPAT | ENT_XML1
        );
        ?></loc><lastmod><?php
        echo htmlspecialchars(
            mb_substr($page->getTimeStamp(), 0, -9),
            ENT_COMPAT | ENT_XML1
        );
        ?></lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url><?php
    }
}
?>
</urlset>
