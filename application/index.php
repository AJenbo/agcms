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

// Redirect old urls
if (isset($_GET['kat']) || isset($_GET['side'])) {
    $category = null;
    if (isset($_GET['kat'])) {
        $category = ORM::getOne(Category::class, $_GET['kat']);
    }
    if (isset($_GET['side'])) {
        $page = ORM::getOne(Page::class, $_GET['side']);
        if ($page && !$category) {
            $category = $page->getPrimaryCategory();
        }
    }
    $newUrl = '/' . ($category ? $category->getSlug(true) : '')
    . ($page ? $page->getSlug(true) : '');
    if ($newUrl === '/') {
        $newUrl .= '?sog=1&q=&varenr=&sogikke=&minpris=&maxpris=&maerke=';
    }

    redirect($newUrl);
}

$_GET = fullMysqliEscape($_GET);

$activMenu =& $GLOBALS['generatedcontent']['activmenu'];

//Always blank kads
if (@$_GET['sog']
    || @$_GET['q']
    || @$_GET['varenr']
    || @$_GET['sogikke']
    || @$_GET['minpris']
    || @$_GET['maxpris']
    || @$_GET['maerke']
    || @$_GET['brod']
) {
    $activMenu = -1;
}

/**
 * Hvis siden er kendt men katagorien ikke så find den første passende
 * katagori, hent også side indholdet.
 */
if (@$GLOBALS['side']['id'] > 0 && $activMenu) {
    $bind = db()->fetchOne(
        "
        SELECT bind.kat,
            sider.navn,
            sider.burde,
            sider.fra,
            sider.text,
            sider.pris,
            sider.for,
            sider.krav,
            sider.maerke,
            sider.varenr,
            UNIX_TIMESTAMP(sider.dato) AS dato
        FROM bind
        JOIN sider
        ON bind.side = sider.id
        WHERE side = ".$GLOBALS['side']['id']
    );
    if ($bind['dato']) {
        Cache::addUpdateTime($bind['dato']);
    } else {
        Cache::addLoadedTable('sider');
    }
    Cache::addLoadedTable('bind');

    $activMenu                 = $bind['kat'];
    $GLOBALS['side']['navn']   = $bind['navn'];
    $GLOBALS['side']['burde']  = $bind['burde'];
    $GLOBALS['side']['fra']    = $bind['fra'];
    $GLOBALS['side']['text']   = $bind['text'];
    $GLOBALS['side']['pris']   = $bind['pris'];
    $GLOBALS['side']['for']    = $bind['for'];
    $GLOBALS['side']['krav']   = $bind['krav'];
    $GLOBALS['side']['maerke'] = $bind['maerke'];
    $GLOBALS['side']['varenr'] = $bind['varenr'];
    $GLOBALS['side']['dato']   = $bind['dato'];
    unset($bind);
} elseif (@$GLOBALS['side']['id'] > 0) {
    //Hent side indhold
    $page = ORM::getOne(Page::class, $GLOBALS['side']['id']);
    if ($page->getTimeStamp()) {
        Cache::addUpdateTime($page->getTimeStamp());
    }

    $GLOBALS['side']['navn']   = $page->getTitle();
    $GLOBALS['side']['burde']  = $page->getOldPriceType();
    $GLOBALS['side']['fra']    = $page->getPriceType();
    $GLOBALS['side']['text']   = $page->getHtml();
    $GLOBALS['side']['pris']   = $page->getPrice();
    $GLOBALS['side']['for']    = $page->getOldPrice();
    $GLOBALS['side']['krav']   = $page->getRequirementId();
    $GLOBALS['side']['maerke'] = $page->getBrandId();
    $GLOBALS['side']['varenr'] = $page->getSku();
    $GLOBALS['side']['dato']   = $page->getTimestamp();
}

if ($activMenu > 0) {
    //get category branch and keywords
    $categoryIds = [];
    $keywords = [];
    $category = ORM::getOne(Category::class, $activMenu);
    do {
        $categoryIds[] = $category->getId();
        $keyword = xhtmlEsc($category->getTitle());
        $keywords[] = trim($keyword);
    } while ($category = $category->getParent());
    $GLOBALS['kats'] = array_reverse($categoryIds);
    $GLOBALS['generatedcontent']['keywords'] = implode(',', array_reverse($keywords));
}

//crumbs start
if (@$GLOBALS['kats']) {
    foreach ($GLOBALS['kats'] as $categoryId) {
        $category = ORM::getOne(Category::class, $categoryId);
        $GLOBALS['generatedcontent']['crumbs'][] = [
            'name' => xhtmlEsc($category->getTitle()),
            'link' => '/' . $category->getSlug(),
            'icon' => $category->getIconPath(),
        ];
    }
    $GLOBALS['generatedcontent']['crumbs'] = array_reverse(
        array_values($GLOBALS['generatedcontent']['crumbs'])
    );
}
//crumbs end

//Get list of top categorys on the site.
$categories = ORM::getByQuery(Category::class,
    "
    SELECT *
    FROM `kat`
    WHERE kat.vis != " . CATEGORY_HIDDEN . "
        AND kat.bind = 0
        AND (id IN (SELECT bind FROM kat WHERE vis != " . CATEGORY_HIDDEN . ")
            OR id IN (SELECT kat FROM bind)
        )
    ORDER BY `order`, navn ASC
    "
);
Cache::addLoadedTable('bind');
foreach ($categories as $category) {
    if ($category->isVisable()) {
        $subs = null;
        $kats = $GLOBALS['kats'] ?? [];
        if ($category->getId() === (int) reset($kats) ?: null) {
            $subs = menu(0, $category->getWeightedChildren());
        }

        $GLOBALS['generatedcontent']['menu'][] = [
            'id' => $category->getId(),
            'name' => xhtmlEsc($category->getTitle()),
            'link' => '/' . $category->getSlug(),
            'icon' => $category->getIconPath(),
            'sub' => $category->getChildren(true) ? true : false,
            'subs' => $subs
        ];
    }
}

//Front page pages
$pages = ORM::getByQuery(Page::class,
    "
    SELECT *
    FROM bind
    JOIN sider
    ON bind.side = sider.id
    WHERE kat = 0
    "
);
Cache::addLoadedTable('bind');

foreach ($pages as $page) {
    $GLOBALS['generatedcontent']['sider'][] = [
        'id' => $page->getId(),
        'name' => xhtmlEsc($page->getTitle()),
        'link' => '/' . $page->getSlug(),
    ];
}

//TODO catch none existing kats
//Get page content and type
if (!empty($_GET['sog'])) {
    $GLOBALS['generatedcontent']['contenttype'] = 'search';

    $text = '';

    if (@$GLOBALS['side']['404']) {
        header('HTTP/1.1 404 Not Found');
        $text .= '<p>' . _('Page could not be found. Try searching for a similar page.') . '</p>';
    }

    $text .= '<form action="/" method="get"><table>';
    $text .= '<tr><td>'._('Contains').'</td><td>';
    $text .= '<input name="q" size="31" value="';
    if (@$GLOBALS['side']['404']) {
        $text = preg_replace(
            ['/-/u', '/\/kat[0-9]+-(.*?)\//u', '/\/side[0-9]+-(.*?)[.]html/u'],
            [' ', ' \1 ', ' \1 '],
            urldecode($_SERVER['REQUEST_URI'])
        );
        $text = xhtmlEsc(trim($text));
    }
    $text .= '" /></td>';
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
} elseif (isset($_GET['q']) || !empty($maerke)) {
    $GLOBALS['generatedcontent']['contenttype'] = 'tiles';

    //Temporarly store the katalog number so it can be restored when search is over
    $temp_kat = $activMenu;

    $pages = [];
    if ((!empty($_GET['maerke']) || @$maerke)
        && empty($_GET['q'])
        && empty($_GET['varenr'])
        && empty($_GET['sogikke'])
        && empty($_GET['minpris'])
        && empty($_GET['maxpris'])
    ) {
        //Brand only search
        $GLOBALS['generatedcontent']['contenttype'] = 'brand';
        if (@$_GET['maerke'] && empty($maerke)) {
            $maerke = $_GET['maerke'];
        }
        $maerkeet = db()->fetchOne(
            "
            SELECT `id`, `navn`, `link`, ico
            FROM `maerke`
            WHERE id = " . $maerke
        );

        Cache::addLoadedTable('maerke');

        $GLOBALS['generatedcontent']['brand'] = [
            'id' => $maerkeet['id'],
            'name' => xhtmlEsc($maerkeet['navn']),
            'xlink' => $maerkeet['link'],
            'icon' => $maerkeet['ico'],
        ];

        $wheresider = " AND `maerke` = '" . $maerkeet['id'] . "'";
        $pages = searchListe(false, $wheresider);
    } else {
        //Full search
        $wheresider = "";
        if (!empty($_GET['varenr'])) {
            $wheresider .= " AND varenr LIKE '" . db()->esc($_GET['varenr']) . "%'";
        }
        if (!empty($_GET['minpris'])) {
            $wheresider .= " AND pris > " . (int) $_GET['minpris'];
        }
        if (!empty($_GET['maxpris'])) {
            $wheresider .= " AND pris < " . (int) $_GET['maxpris'];
        }
        if (!empty($_GET['maerke'])) {
            $wheresider = " AND `maerke` = '" . (int) $_GET['maerke'] . "'";
        }
        if (!empty($_GET['sogikke'])) {
            $wheresider .= " AND !MATCH (navn, text) AGAINST('" . db()->esc($_GET['sogikke']) ."') > 0";
        }
        $pages = searchListe(@$_GET['q'], $wheresider);
    }

    //Draw the list
    if (count($pages) === 1
        && $GLOBALS['generatedcontent']['contenttype'] != 'brand'
    ) {
        $page = array_shift($pages);
        $url = $page->getCanonicalLink(true, $category);
        redirect($url, 302);
    } else {
        foreach ($pages as $page) {
            //Search categories does not have a fixed number, use first fixed per page
            $category = null;
            if ($GLOBALS['generatedcontent']['activmenu']) {
                $category = ORM::getOne(Category::class, $GLOBALS['generatedcontent']['activmenu']);
            }

            vare($page, CATEGORY_GALLERY, $category);
        }
    }
    $activMenu = $temp_kat;

    $wherekat = '';
    if (@$_GET['sogikke']) {
        $wherekat .= ' AND !MATCH (navn) AGAINST(\''.$_GET['sogikke'].'\') > 0';
    }
    searchMenu(@$_GET['q'], $wherekat);
} elseif (@$GLOBALS['side']['id'] > 0) {
    $GLOBALS['generatedcontent']['contenttype'] = 'product';
    side();
} elseif ($activMenu > 0) {
    $category = ORM::getOne(Category::class, $GLOBALS['generatedcontent']['activmenu']);
    liste($category);
    if (@$GLOBALS['side']['id'] > 0) {
        $GLOBALS['generatedcontent']['contenttype'] = 'product';
    } elseif (Cache::get('kat' . $activMenu . 'type') == 2) {
        $GLOBALS['generatedcontent']['contenttype'] = 'list';
    } elseif (Cache::get('kat' . $activMenu . 'type') == 1) {
        $GLOBALS['generatedcontent']['contenttype'] = 'tiles';
    }
} else {
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

    $GLOBALS['generatedcontent']['contenttype'] = 'front';
    $GLOBALS['generatedcontent']['text'] = $special['text'];
    unset($special);
}

//Extract title for current page.
if (!empty($maerkeet)) {
    $GLOBALS['generatedcontent']['title'] = $maerkeet['navn'];
} elseif (isset($GLOBALS['side']['navn'])) {
    $GLOBALS['generatedcontent']['title'] = xhtmlEsc($GLOBALS['side']['navn']);
    //Add page title to keywords
    if (!empty($GLOBALS['generatedcontent']['keywords'])) {
        $GLOBALS['generatedcontent']['keywords'] .= "," . xhtmlEsc($GLOBALS['side']['navn']);
    } else {
        $GLOBALS['generatedcontent']['keywords'] = xhtmlEsc($GLOBALS['side']['navn']);
    }
} elseif (!empty($GLOBALS['side']['id'])) {
    $page = ORM::getOne(Page::class, $GLOBALS['side']['id']);
    Cache::addUpdateTime($page->getTimeStamp());
    $GLOBALS['generatedcontent']['title'] = xhtmlEsc($page->getTitle());
}

$category = null;
if ($activMenu) {
    $category = ORM::getOne(Category::class, $activMenu);
}
if (empty($GLOBALS['generatedcontent']['title']) && $category) {
    $GLOBALS['generatedcontent']['title'] = xhtmlEsc($category->getTitle());

    if ($category->getIconPath()) {
        $icon = db()->fetchOne(
            "
            SELECT `alt`
            FROM `files`
            WHERE path = '" . db()->esc($category->getIconPath()) . "'"
        );
        Cache::addLoadedTable('files');
    }

    if (!empty($icon['alt']) && $GLOBALS['generatedcontent']['title']) {
        $GLOBALS['generatedcontent']['title'] .= ' ' . xhtmlEsc($icon['alt']);
    } elseif (!empty($icon['alt'])) {
        $GLOBALS['generatedcontent']['title'] = xhtmlEsc($icon['alt']);
    } elseif (!$GLOBALS['generatedcontent']['title']) {
        $icon['path'] = pathinfo($category->getIconPath());
        $GLOBALS['generatedcontent']['title'] = xhtmlEsc(
            ucfirst(
                preg_replace('/-/ui', ' ', $icon['path']['filename'])
            )
        );
    }
} elseif (empty($GLOBALS['generatedcontent']['title']) && @$_GET['sog'] == 1) {
    $GLOBALS['generatedcontent']['title'] = 'Søg på ' . xhtmlEsc($GLOBALS['_config']['site_name']);
}
if (empty($GLOBALS['generatedcontent']['title'])) {
    $GLOBALS['generatedcontent']['title'] = xhtmlEsc($GLOBALS['_config']['site_name']);
}

//Get email
$GLOBALS['generatedcontent']['email'] = array_shift($GLOBALS['_config']['email']);
if ($category && $category->getEmail()) {
    $GLOBALS['generatedcontent']['email'] = $category->getEmail();
}

if (empty($delayprint)) {
    doConditionalGet(Cache::getUpdateTime());

    require_once 'theme/index.php';
}
