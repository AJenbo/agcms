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

header('Content-Type:text/xml;charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8" ?>';

require_once 'inc/config.php';
require_once 'inc/mysqli.php';
require_once 'inc/functions.php';

//Open database
$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

/**
 * Print XML for content bellonging to a category
 *
 * @param int $id Id of category
 *
 * @return null
 */
function listKats(int $id)
{
    global $mysqli;

    $kats = $mysqli->fetchArray(
        "
        SELECT id, navn
        FROM kat
        WHERE bind = " . $id
    );

    for ($ki=0; $ki<count($kats); $ki++) {
        //print xml
        ?><url>
        <loc><?php echo $GLOBALS['_config']['base_url'] ?>/kat<?php
        echo $kats[$ki]['id'] . '-' . clearFileName($kats[$ki]['navn']);
        ?>/</loc>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
        </url><?php
        $url = '/kat' . $kats[$ki]['id'] . '-' . clearFileName($kats[$ki]['navn']);
        listPages($kats[$ki]['id'], $url);
        listKats($kats[$ki]['id']);
    }
}

/**
 * Print XML for pages bellonging to a category
 *
 * @param int    $id      Id of category
 * @param string $katName Url of category
 *
 * @return null
 */
function listPages(int $id, string $katName)
{
    global $mysqli;

    $binds = $mysqli->fetchArray("SELECT side FROM bind WHERE kat = " . $id);
    foreach ($binds as $bind) {
        $sider = $mysqli->fetchArray(
            "
            SELECT navn, dato
            FROM sider
            WHERE id = " . $bind['side'] . "
            LIMIT 1
            "
        );
        //print xml
        ?><url><loc><?php
        echo $GLOBALS['_config']['base_url'] . $katName . '/side'
        . $bind['side'] . '-' . clearFileName($sider[0]['navn']) . '.html';
        ?></loc>
        <lastmod><?php
        echo mb_substr($sider[0]['dato'], 0, -9, 'UTF-8');
        ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
        </url><?php
    }
}


$special = $mysqli->fetchArray("SELECT dato FROM special WHERE id = 1");
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
    <url>
        <loc><?php
echo $GLOBALS['_config']['base_url'];
?>/</loc>
        <lastmod><?php
echo mb_substr($special[0]['dato'], 0, -9, 'UTF-8');
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
    listKats("0");
?>
</urlset>
