<?php
/**
 * Handle request for the site and decide on how to generate the page
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

if (!empty($_SESSION['faktura']['quantities'])) {
    Cache::addUpdateTime(time());
}

//If the database is older then the users cache, send 304 not modified
//WARNING: this results in the site not updating if new files are included later,
//the remedy is to update the database when new cms files are added.
if (empty($delayprint)) {
    doConditionalGet(Cache::getUpdateTime());
}

//Front page pages
$pages = ORM::getByQuery(
    Page::class,
    "
    SELECT *
    FROM bind
    JOIN sider
    ON bind.side = sider.id
    WHERE kat = 0
    ORDER BY sider.`navn` ASC
    "
);
Cache::addLoadedTable('bind');

foreach ($pages as $page) {
    $GLOBALS['generatedcontent']['sider'][] = [
        'id'   => $page->getId(),
        'name' => xhtmlEsc($page->getTitle()),
        'link' => '/' . $page->getSlug(),
    ];
}
$emails = array_keys($GLOBALS['_config']['emails']);
$GLOBALS['generatedcontent']['email'] = reset($emails);
$GLOBALS['generatedcontent']['crumbs'] = [];
$GLOBALS['generatedcontent']['title'] = xhtmlEsc($GLOBALS['_config']['site_name']);
$GLOBALS['generatedcontent']['activmenu'] = -1;
$GLOBALS['generatedcontent']['canonical'] = '';

$maerkeId = $maerkeId ?? null;
$activeCategory = $activeCategory ?? null;
$activePage = $activePage ?? null;

$keywords = [];
$categoryIds = [];
if ($activeCategory) {
    $crumbs = [];
    foreach ($activeCategory->getBranch() as $category) {
        $categoryIds[] = $category->getId();
        $keywords[] = trim(xhtmlEsc($category->getTitle()));
        $crumbs[] = [
            'name' => xhtmlEsc($category->getTitle()),
            'link' => '/' . $category->getSlug(),
            'icon' => $category->getIconPath(),
        ];
    };

    $GLOBALS['generatedcontent']['crumbs'] = $crumbs;
    $GLOBALS['generatedcontent']['activmenu'] = $activeCategory->getId();
}

//Get list of top categorys on the site.
$categories = ORM::getByQuery(
    Category::class,
    "
    SELECT *
    FROM `kat`
    WHERE kat.vis != " . Category::HIDDEN . "
        AND kat.bind = 0
        AND (id IN (SELECT bind FROM kat WHERE vis != " . Category::HIDDEN . ")
            OR id IN (SELECT kat FROM bind)
        )
    ORDER BY `order`, navn ASC
    "
);
Cache::addLoadedTable('bind');
$GLOBALS['generatedcontent']['menu'] = menu($categories, $categoryIds);

$listedPages = [];
$pageType = 'front';
if (!empty($_GET['sog'])) {
    $pageType = 'search';
} elseif ($maerkeId) {
    $pageType = 'brand';
    $maerkeet = db()->fetchOne(
        "
        SELECT `id`, `navn`, `link`, ico
        FROM `maerke`
        WHERE id = " . $maerkeId
    );
    Cache::addLoadedTable('maerke');

    $GLOBALS['generatedcontent']['title'] = xhtmlEsc($maerkeet['navn']);
    $GLOBALS['generatedcontent']['brand'] = [
        'id' => $maerkeet['id'],
        'name' => xhtmlEsc($maerkeet['navn']),
        'xlink' => $maerkeet['link'],
        'icon' => $maerkeet['ico'],
    ];

    $where = " AND `maerke` = '" . $maerkeet['id'] . "'";
    $listedPages = searchListe($_GET['q'] ?? '', $where);
} elseif ($activeCategory && !$activePage) {
    $pageType = $activeCategory->getRenderMode() == Category::GALLERY ? 'tiles' : 'list';
    $listedPages = $activeCategory->getPages();
    if (count($listedPages) === 1) {
        $activePage = array_shift($listedPages);
        $pageType = 'product';
        $listedPages = [];
    }
} elseif (isset($_GET['q'])) {
    // Brand search
    if (empty($_GET['varenr'])
        && empty($_GET['minpris'])
        && empty($_GET['maxpris'])
        && !empty($_GET['maerke'])
        && empty($_GET['sogikke'])
    ) {
        $maerkeet = db()->fetchOne(
            "
            SELECT `id`, `navn`
            FROM `maerke`
            WHERE id = " . (int) $_GET['maerke']
        );
        if ($maerkeet) {
            $redirectUrl = '/m%C3%A6rke' . $maerkeet['id'] . '-' . rawurlencode(clearFileName($maerkeet['navn'])) . '/';
            redirect($redirectUrl, 301);
        }
    }

    //Full search
    $where = "";
    if (!empty($_GET['varenr'])) {
        $where .= " AND varenr LIKE '" . db()->esc($_GET['varenr']) . "%'";
    }
    if (!empty($_GET['minpris'])) {
        $where .= " AND pris > " . (int) $_GET['minpris'];
    }
    if (!empty($_GET['maxpris'])) {
        $where .= " AND pris < " . (int) $_GET['maxpris'];
    }
    if (!empty($_GET['maerke'])) {
        $where = " AND `maerke` = '" . (int) $_GET['maerke'] . "'";
    }
    if (!empty($_GET['sogikke'])) {
        $where .= " AND !MATCH (navn, text) AGAINST('" . db()->esc($_GET['sogikke']) ."') > 0";
    }
    $listedPages = searchListe($_GET['q'] ?? '', $where);
    if (count($listedPages) === 1) {
        $page = array_shift($listedPages);
        redirect($page->getCanonicalLink(), 302);
    }

    $wherekat = '';
    if (!empty($_GET['sogikke'])) {
        $wherekat .= " AND !MATCH (navn) AGAINST('" . db()->esc($_GET['sogikke']) . "') > 0";
    }
    searchMenu($_GET['q'] ?? '', $wherekat);

    $GLOBALS['generatedcontent']['title'] = 'Søg på ' . xhtmlEsc($GLOBALS['_config']['site_name']);
    $pageType = 'tiles';
}

if ($listedPages) {
    $pageArray = [];
    foreach ($listedPages as $page) {
        $pageArray[] = [
            'id'     => $page->getId(),
            'navn'   => $page->getTitle(),
            'object' => $page,
        ];
    }
    $pageArray = arrayNatsort($pageArray, 'id', 'navn', 'asc');
    foreach ($pageArray as $item) {
        $page = $item['object'];

        if (!$activeCategory || $activeCategory->getRenderMode() === Category::GALLERY) {
            $GLOBALS['generatedcontent']['list'][] = [
                'id' => $page->getId(),
                'name' => xhtmlEsc($page->getTitle()),
                'date' => $page->getTimeStamp(),
                'link' => $page->getCanonicalLink($activeCategory),
                'icon' => $page->getImagePath(),
                'text' => $page->getExcerpt(),
                'price' => [
                    'before' => $page->getOldPrice(),
                    'now' => $page->getPrice(),
                    'from' => $page->getPriceType(),
                    'market' => $page->getOldPriceType(),
                ]
            ];
        } else {
            $GLOBALS['generatedcontent']['list'][] = [
                'id' => $page->getId(),
                'name' => xhtmlEsc($page->getTitle()),
                'date' => $page->getTimeStamp(),
                'link' => $page->getCanonicalLink($activeCategory),
                'serial' => $page->getSku(),
                'price' => [
                    'before' => $page->getOldPrice(),
                    'now' => $page->getPrice(),
                ]
            ];
        }
    }
}

if ($activeCategory && empty($GLOBALS['generatedcontent']['title'])) {
    $title = trim($activeCategory->getTitle());

    if ($activeCategory->getIconPath()) {
        $icon = db()->fetchOne(
            "
            SELECT `alt`
            FROM `files`
            WHERE path = '" . db()->esc($activeCategory->getIconPath()) . "'"
        );
        Cache::addLoadedTable('files');
        if (!empty($icon['alt'])) {
            $title .= ($title ? ' ' : '') . $icon['alt'];
        } elseif (!$title) {
            $path = pathinfo($activeCategory->getIconPath());
            $title = ucfirst(preg_replace('/-/ui', ' ', $path['filename']));
        }
    }

    $GLOBALS['generatedcontent']['title'] = xhtmlEsc($title);
}

//Get page content and type
if ($pageType === 'front') {
    $special = db()->fetchOne(
        "
        SELECT text, UNIX_TIMESTAMP(dato) AS dato
        FROM special
        WHERE id = 1
        "
    );
    if ($special['dato']) {
        Cache::addUpdateTime($special['dato']);
    } else {
        Cache::addLoadedTable('special');
    }

    $GLOBALS['generatedcontent']['text'] = $special['text'];
} elseif ($pageType === 'search') {
    $GLOBALS['generatedcontent']['title'] = 'Søg på ' . xhtmlEsc($GLOBALS['_config']['site_name']);

    $text = '<form action="/" method="get"><table>';
    $text .= '<tr><td>'._('Contains').'</td><td>';
    $text .= '<input name="q" size="31" /></td>';
    $text .= '<td><input type="submit" value="'._('Search').'" /></td></tr>';
    $text .= '<tr><td>'._('Part No.').'</td>';
    $text .= '<td><input name="varenr" size="31" value="" maxlength="63" /></td>';
    $text .= '</tr><tr><td>'._('Without the words').'</td><td>';
    $text .= '<input name="sogikke" size="31" value="" /></td></tr>';
    $text .= '<tr><td>'._('Min price').'</td><td>';
    $text .= '<input name="minpris" size="5" maxlength="11" value="" />,-</td></tr>';
    $text .= '<tr><td>'._('Max price').'&nbsp;</td><td>';
    $text .= '<input name="maxpris" size="5" maxlength="11" value="" />,-</td></tr';
    $text .= '><tr><td>'._('Brand:').'</td><td><select name="maerke">';
    $text .= '<option value="0">'._('All').'</option>';

    $maerker = db()->fetchArray(
        "
        SELECT `id`, `navn`
        FROM `maerke`
        ORDER BY `navn` ASC
        "
    );
    Cache::addLoadedTable('maerke');

    foreach ($maerker as $value) {
        $text .= '<option value="'.$value['id'].'">';
        $text .= xhtmlEsc($value['navn']) . '</option>';
    }
    $text .= '</select></td></tr></table></form>';
    $GLOBALS['generatedcontent']['text'] = $text;
} elseif ($activePage) {
    $pageType = 'product';
    $GLOBALS['generatedcontent']['canonical']       = $activePage->getCanonicalLink();
    $GLOBALS['generatedcontent']['title']           = xhtmlEsc($activePage->getTitle());
    $GLOBALS['generatedcontent']['headline']        = $activePage->getTitle();
    $GLOBALS['generatedcontent']['serial']          = $activePage->getSku();
    $GLOBALS['generatedcontent']['datetime']        = $activePage->getTimestamp();
    $GLOBALS['generatedcontent']['price']['now']    = $activePage->getPrice();
    $GLOBALS['generatedcontent']['price']['new']    = $activePage->getPrice();
    $GLOBALS['generatedcontent']['price']['from']   = $activePage->getPriceType();
    $GLOBALS['generatedcontent']['price']['before'] = $activePage->getOldPrice();
    $GLOBALS['generatedcontent']['price']['old']    = $activePage->getOldPrice();
    $GLOBALS['generatedcontent']['price']['market'] = $activePage->getOldPriceType();
    if ($activeCategory) {
        $GLOBALS['generatedcontent']['email'] = $activeCategory->getEmail();
    }

    $html = $activePage->getHtml();
    $lists = db()->fetchArray(
        "
        SELECT id
        FROM `lists`
        WHERE `page_id` = " . $activePage->getId()
    );
    Cache::addLoadedTable('lists');

    foreach ($lists as $list) {
        $html = '<div id="table' . $list['id'] . '">';
        $table_html = getTable(
            $list['id'],
            null,
            $activeCategory ? $activeCategory->getId() : null
        );
        $html .= $table_html['html'];
        $html .= '</div>';
    }
    $GLOBALS['generatedcontent']['text'] = $html;

    if ($activePage->getRequirementId()) {
        $krav = db()->fetchOne(
            "
            SELECT id, navn
            FROM krav
            WHERE id = " . $activePage->getRequirementId()
        );
        Cache::addLoadedTable('krav');

        $GLOBALS['generatedcontent']['requirement']['icon'] = '';
        $GLOBALS['generatedcontent']['requirement']['name'] = $krav['navn'];
        $GLOBALS['generatedcontent']['requirement']['link'] = '/krav/'
        . $krav['id'] . '/' . clearFileName($krav['navn']) . '.html';
    }

    if ($activePage->getBrandId()) {
        $brand = db()->fetchOne(
            "
            SELECT `id`, `navn`, `link`, `ico`
            FROM `maerke`
            WHERE `id` = " . $activePage->getBrandId() . "
            ORDER BY `navn`
            "
        );
        Cache::addLoadedTable('maerke');

        $GLOBALS['generatedcontent']['brands'][] = [
            'name' => $brand['navn'],
            'link' => '/mærke' . $brand['id'] . '-' . clearFileName($brand['navn']) . '/',
            'xlink' => $brand['link'],
            'icon' => $brand['ico']
        ];
    }

    foreach ($activePage->getAccessories() as $page) {
        $GLOBALS['generatedcontent']['accessories'][] = [
            'name' => $page->getTitle(),
            'link' => $page->getCanonicalLink(),
            'icon' => $page->getImagePath(),
            'text' => $page->getExcerpt(),
            'price' => [
                'now' => $page->getPrice(),
                'from' => $page->getPriceType(),
                'before' => $page->getOldPrice(),
                'market' => $page->getOldPriceType(),
            ],
        ];
    }

    $keywords[] = $activePage->getTitle();
    $GLOBALS['side']['id'] = $activePage->getId(); // Compatible with templates
}

$GLOBALS['generatedcontent']['keywords'] = xhtmlEsc(implode(',', $keywords));
$GLOBALS['generatedcontent']['contenttype'] = $pageType;

if (empty($delayprint)) {
    doConditionalGet(Cache::getUpdateTime());
    require_once _ROOT_ . '/theme/index.php';
}
