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

$pages = ORM::getByQuery(
    Page::class,
    "
    SELECT * FROM sider
    WHERE `pris` > 0 AND `navn` != ''
    ORDER BY ``varenr` DESC
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
    . $GLOBALS['_config']['base_url'] . encodeUrl($page->getImagePath()) . '</img><link>'
    . $GLOBALS['_config']['base_url'] . encodeUrl($page->getCanonicalLink()) . '</link>';

    $categoryTitles = [];
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
db()->close();
echo '</products>';
