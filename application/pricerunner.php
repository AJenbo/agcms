<?php
/**
 * Print feed for pricerunner.com
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
doConditionalGet(Cache::getUpdateTime());

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

$sider = db()->fetchArray(
    "
    SELECT sider.id,
        `pris`,
        varenr,
        sider.maerke,
        sider.navn,
        billed,
        kat.id AS kat_id
    FROM sider
    JOIN bind ON (side = sider.id)
    JOIN kat ON (kat.id = kat)
    WHERE `pris` >0
      AND sider.`navn` != ''
    GROUP BY id
    ORDER BY `sider`.`varenr` DESC
    "
);
foreach ($sider as $key => $page) {
    $category = ORM::getOne(Category::class, $page['kat_id']);
    if ($category->isInactive()) {
        continue;
    }

    $name = htmlspecialchars($page['navn'], ENT_COMPAT | ENT_XML1);
    if (!$page['navn'] = trim($name)) {
        continue;
    }

    echo '
    <product>
        <sku>'.$page['id'].'</sku>
        <title>'.$page['navn'].'</title>';
    if (trim($page['varenr'])) {
        echo '<companysku>' . htmlspecialchars($page['varenr'], ENT_COMPAT | ENT_XML1) . '</companysku>';
    }
    echo '<price>' . $page['pris'] . ',00</price>
    <img>' . $GLOBALS['_config']['base_url'] . $page['billed'] . '</img>
    <link>' . $GLOBALS['_config']['base_url'] . '/' . $category->getSlug(true)
    . 'side' . $page['id'] . '-' . rawurlencode(clearFileName($page['navn'])) . '.html</link>';

    $categoryTitles = [];
    if ($page['maerke']) {
        $maerker = db()->fetchOne(
            "
            SELECT `navn`
            FROM maerke
            WHERE id = " . $page['maerke']
        );
        $cleaned = trim(preg_replace($search, $replace, $maerker['navn']));
        if ($cleaned) {
            $categoryTitles[] = htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
        }
        echo '<company>';
        echo htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
        echo '</company>';
    }

    $categories = ORM::getByQuery(
        Category::class,
        "
        SELECT kat.*
        FROM `bind`
        JOIN kat ON kat.id = bind.kat
        WHERE bind.side = " . $page['id']
    );
    foreach ($categories as $category) {
        do {
            $categoryIds[] = $category->getId();
        } while ($category = $category->getParent());
    }
    $categoryIds = array_unique($categoryIds);

    foreach ($categoryIds as $categoryId) {
        $category = ORM::getOne(Category::class, $categoryId);
        $cleaned = trim(preg_replace($search, $replace, $category->getTitle()));
        $cleaned = trim($cleaned);
        if ($cleaned) {
            $categoryTitles[] = htmlspecialchars($cleaned, ENT_NOQUOTES | ENT_XML1);
        }
    }

    $categoryTitles = array_unique(array_reverse($categoryTitles));

    echo '<category>'.implode(' &gt; ', $categoryTitles).'</category>';
    echo '</product>';
}
db()->close();
echo '</products>';
