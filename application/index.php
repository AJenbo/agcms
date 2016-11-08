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

if ($activMenu) {
    $category = ORM::getOne(Category::class, $activMenu);

    //get category branch and keywords
    $categoryIds = [];
    $keywords = [];
    foreach ($category->getBranch() as $node) {
        $categoryIds[] = $node->getId();
        $keyword = xhtmlEsc($node->getTitle());
        $keywords[] = trim($keyword);
    };
    $GLOBALS['kats'] = $categoryIds;
    $GLOBALS['generatedcontent']['keywords'] = implode(',', $keywords);
}

//crumbs start
if (!empty($GLOBALS['kats'])) {
    foreach ($GLOBALS['kats'] as $categoryId) {
        $category = ORM::getOne(Category::class, $categoryId);
        $GLOBALS['generatedcontent']['crumbs'][] = [
            'name' => xhtmlEsc($category->getTitle()),
            'link' => '/' . $category->getSlug(),
            'icon' => $category->getIconPath(),
        ];
    }
    $GLOBALS['generatedcontent']['crumbs'] = array_values($GLOBALS['generatedcontent']['crumbs']);
}
//crumbs end

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

//TODO catch none existing kats
//Get page content and type
if (!empty($_GET['sog'])) {
    $GLOBALS['generatedcontent']['activmenu'] = -1;
    $GLOBALS['generatedcontent']['contenttype'] = 'search';

    $text = '';

    if (!empty($GLOBALS['side']['404'])) {
        header('HTTP/1.1 404 Not Found');
        $text .= '<p>' . _('Page could not be found. Try searching for a similar page.') . '</p>';
    }

    $text .= '<form action="/" method="get"><table>';
    $text .= '<tr><td>'._('Contains').'</td><td>';
    $text .= '<input name="q" size="31" value="';
    if (!empty($GLOBALS['side']['404'])) {
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
} elseif (isset($_GET['q'])) {
    $GLOBALS['generatedcontent']['contenttype'] = 'tiles';

    //Temporarly store the katalog number so it can be restored when search is over
    $temp_kat = $activMenu;

    $pages = [];
    if (!empty($maerke)) {
        //Brand only search
        $GLOBALS['generatedcontent']['contenttype'] = 'brand';
        if (!empty($_GET['maerke'])) {
            $maerke = (int) $_GET['maerke'];
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

        $where = " AND `maerke` = '" . $maerkeet['id'] . "'";
        $pages = searchListe(false, $where);
    } else {
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
        $pages = searchListe($_GET['q'] ?? '', $where);
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
            if ($activMenu) {
                $category = ORM::getOne(Category::class, $activMenu);
            }

            vare($page, $category);
        }
    }

    $wherekat = '';
    if (!empty($_GET['sogikke'])) {
        $wherekat .= " AND !MATCH (navn) AGAINST('" . db()->esc($_GET['sogikke']) . "') > 0";
    }
    searchMenu($_GET['q'] ?? '', $wherekat);
} else {
    $category = null;
    if ($activMenu && empty($GLOBALS['side']['id'])) {
        $category = ORM::getOne(Category::class, $activMenu);
        $pages = $category->getPages();
        if (count($pages) === 1) {
            $GLOBALS['side']['id'] = reset($pages)->getId();
        } elseif ($pages) {

            $pageArray = [];
            foreach ($pages as $page) {
                $pageArray[] = [
                    'id'     => $page->getId(),
                    'navn'   => $page->getTitle(),
                    'object' => $page,
                ];
            }
            $pageArray = arrayNatsort($pageArray, 'id', 'navn', 'asc');
            $pages = [];
            foreach ($pageArray as $item) {
                $pages[] = $item['object'];
            }

            foreach ($pages as $page) {
                vare($page, $category);
            }
        }
    }

    if (!empty($GLOBALS['side']['id'])) {
        $GLOBALS['generatedcontent']['contenttype'] = 'product';
        $page = ORM::getOne(Page::class, $GLOBALS['side']['id']);
        $GLOBALS['side'] = [
            'id'     => $page->getId(),
            'navn'   => $page->getTitle(),
            'burde'  => $page->getOldPriceType(),
            'fra'    => $page->getPriceType(),
            'text'   => $page->getHtml(),
            'pris'   => $page->getPrice(),
            'for'    => $page->getOldPrice(),
            'krav'   => $page->getRequirementId(),
            'maerke' => $page->getBrandId(),
            'varenr' => $page->getSku(),
            'dato'   => $page->getTimeStamp(),
        ];
        Cache::addUpdateTime($page->getTimeStamp());

        $GLOBALS['generatedcontent']['headline'] = $GLOBALS['side']['navn'];
        $GLOBALS['generatedcontent']['serial']   = $GLOBALS['side']['varenr'];
        $GLOBALS['generatedcontent']['datetime'] = $GLOBALS['side']['dato'];
        $GLOBALS['generatedcontent']['text']     = $GLOBALS['side']['text'];

        if ($GLOBALS['side']['krav']) {
            $krav = db()->fetchOne(
                "
                SELECT navn
                FROM krav
                WHERE id = " . $GLOBALS['side']['krav']
            );
            Cache::addLoadedTable('krav');

            $GLOBALS['generatedcontent']['requirement']['icon'] = '';
            $GLOBALS['generatedcontent']['requirement']['name'] = $krav['navn'];
            $GLOBALS['generatedcontent']['requirement']['link'] = '/krav/'
            . $GLOBALS['side']['krav'] . '/' . clearFileName($krav['navn'])
            . '.html';
        }

        $GLOBALS['generatedcontent']['price']['before']  = $GLOBALS['side']['for'];
        $GLOBALS['generatedcontent']['price']['now']    = $GLOBALS['side']['pris'];
        $GLOBALS['generatedcontent']['price']['from']   = $GLOBALS['side']['fra'];
        $GLOBALS['generatedcontent']['price']['market'] = $GLOBALS['side']['burde'];

        unset($GLOBALS['side']['text']);

        //TODO and figure out how to do the sorting using only js
        $GLOBALS['generatedcontent']['text'] .= echoTable($GLOBALS['side']['id']);

        $GLOBALS['generatedcontent']['price']['old']    = $GLOBALS['side']['for'];
        $GLOBALS['generatedcontent']['price']['market'] = $GLOBALS['side']['burde'];
        $GLOBALS['generatedcontent']['price']['new']    = $GLOBALS['side']['pris'];
        $GLOBALS['generatedcontent']['price']['from']   = $GLOBALS['side']['fra'];

        Cache::addLoadedTable('kat');

        $GLOBALS['generatedcontent']['email'] = $GLOBALS['_config']['email'];
        $category = ORM::getOne(Category::class, $activMenu);
        if ($category) {
            $GLOBALS['generatedcontent']['email'] = $category->getEmail();
        }

        if ($GLOBALS['side']['maerke']) {
            $maerker = db()->fetchArray(
                "
                SELECT `id`, `navn`, `link`, `ico`
                FROM `maerke`
                WHERE `id` IN(" . $GLOBALS['side']['maerke'] . ") AND `ico` != ''
                ORDER BY `navn`
                "
            );
            $temp = db()->fetchArray(
                "
                SELECT `id`, `navn`, `link`, `ico`
                FROM `maerke`
                WHERE `id` IN(" . $GLOBALS['side']['maerke'] . ") AND `ico` = ''
                ORDER BY `navn`
                "
            );
            Cache::addLoadedTable('maerke');
            $maerker = array_merge($maerker, $temp);

            Cache::addLoadedTable('maerke');

            foreach ($maerker as $value) {
                $GLOBALS['generatedcontent']['brands'][] = [
                    'name' => $value['navn'],
                    'link' => '/mærke' . $value['id'] . '-' . clearFileName($value['navn']) . '/',
                    'xlink' => $value['link'],
                    'icon' => $value['ico']
                ];
            }
        }

        $accessories = ORM::getOne(Page::class, $GLOBALS['side']['id'])->getAccessories();
        foreach ($accessories as $page) {
            $GLOBALS['generatedcontent']['accessories'][] = [
                'name' => $page->getTitle(),
                'link' => $page->getCanonicalLink(),
                'icon' => $page->getImagePath(),
                'text' => $page->getExcerpt(),
                'price' => [
                    'before' => $page->getOldPrice(),
                    'now' => $page->getPrice(),
                    'from' => $page->getPriceType(),
                    'market' => $page->getOldPriceType(),
                ],
            ];
        }
    } elseif ($category && $category->getRenderMode() == Category::LIST) {
        $GLOBALS['generatedcontent']['contenttype'] = 'list';
    } elseif ($category && $category->getRenderMode() == Category::GALLERY) {
        $GLOBALS['generatedcontent']['contenttype'] = 'tiles';
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
} elseif (empty($GLOBALS['generatedcontent']['title']) && !empty($_GET['sog'])) {
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

    require_once _ROOT_ . '/theme/index.php';
}
