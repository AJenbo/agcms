<?php

class Render
{
    private static $updateTime = 0;
    private static $loadedTables = [];
    private static $pageType = 'front';

    /**
     * @param string $key The cache key
     * @param mixed  $key The value to store
     *
     * @return mixed
     */
    public static function addUpdateTime(int $timeStamp)
    {
        self::$updateTime = max(self::$updateTime, $timeStamp);
    }

    /**
     * @param string $tableName The table name
     */
    public static function addLoadedTable(string $tableName)
    {
        self::$loadedTables[$tableName] = true;
    }

    /**
     * @param string $tableName The table name
     */
    public static function getUpdateTime(bool $checkDb = true): int
    {
        foreach (get_included_files() as $filename) {
            self::$updateTime = max(self::$updateTime, filemtime($filename));
        }

        if ($checkDb) {
            $timeOffset = db()->getTimeOffset();
            $tables = db()->fetchArray("SHOW TABLE STATUS" . (self::$loadedTables ? " WHERE Name IN('" . implode("', '", array_keys(self::$loadedTables)) . "')" : ""));
            foreach ($tables as $table) {
                self::$updateTime = max(self::$updateTime, strtotime($table['Update_time']) + $timeOffset);
            }
        }

        if (self::$updateTime <= 0) {
            return time();
        }

        return self::$updateTime;
    }

    /**
     * Set Last-Modified and ETag http headers
     * and use cache if no updates since last visit
     *
     * @param int $timestamp Unix time stamp of last update to content
     */
    public static function sendCacheHeader(int $timestamp = null)
    {
        if (!empty($_SESSION['faktura']['quantities'])) {
            $timestamp = time();
        }
        if (!$timestamp) {
            $timestamp = self::getUpdateTime();
        }
        if (!$timestamp) {
            return;
        }

        // A PHP implementation of conditional get, see
        // http://fishbowl.pastiche.org/archives/001132.html
        $last_modified = mb_substr(date('r', $timestamp), 0, -5) . 'GMT';
        $etag = (string) $timestamp;

        // Send the headers
        header('Cache-Control: max-age=0, must-revalidate'); // HTTP/1.1
        header('Pragma: no-cache');                          // HTTP/1.0
        header('Last-Modified: ' . $last_modified);
        header('ETag: ' . $etag);

        // See if the client has provided the required headers
        $if_modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? false;
        $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? false;
        if (!$if_modified_since && !$if_none_match) {
            return;
        }
        // At least one of the headers is there - check them
        if ($if_none_match && $if_none_match !== $etag) {
            return; // etag is there but doesn't match
        }
        if ($if_modified_since && $if_modified_since !== $last_modified) {
            return; // if-modified-since is there but doesn't match
        }

        // Nothing has changed since their last request - serve a 304 and exit
        ini_set('zlib.output_compression', 0);
        header('HTTP/1.1 304 Not Modified', true, 304);
        die();
    }

    public static function prepareData()
    {
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
        self::addLoadedTable('bind');

        foreach ($pages as $page) {
            $GLOBALS['generatedcontent']['sider'][] = [
                'id'   => $page->getId(),
                'name' => xhtmlEsc($page->getTitle()),
                'link' => '/' . $page->getSlug(),
            ];
        }
        $emails = first(Config::get('emails'))['address'];
        $GLOBALS['generatedcontent']['email'] = $emails;
        $GLOBALS['generatedcontent']['crumbs'] = [];
        $GLOBALS['generatedcontent']['title'] = xhtmlEsc(Config::get('site_name'));
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
            ORDER BY `order`, navn
            "
        );
        self::addLoadedTable('bind');
        $GLOBALS['generatedcontent']['menu'] = menu($categories, $categoryIds);

        $listedPages = [];
        if (!empty($_GET['sog'])) {
            self::$pageType = 'search';
        } elseif ($maerkeId) {
            self::$pageType = 'brand';
            $maerkeet = db()->fetchOne(
                "
                SELECT `id`, `navn`, `link`, ico
                FROM `maerke`
                WHERE id = " . $maerkeId
            );
            self::addLoadedTable('maerke');

            $GLOBALS['generatedcontent']['title'] = xhtmlEsc($maerkeet['navn']);
            $GLOBALS['generatedcontent']['brand'] = [
                'id' => $maerkeet['id'],
                'name' => xhtmlEsc($maerkeet['navn']),
                'xlink' => $maerkeet['link'],
                'icon' => $maerkeet['ico'],
            ];

            $where = " AND `maerke` = '" . $maerkeet['id'] . "'";
            $listedPages = searchListe('', $where);
        } elseif ($activePage) {
            self::$pageType = 'product';
        } elseif ($activeCategory) {
            self::$pageType = $activeCategory->getRenderMode() == Category::GALLERY ? 'tiles' : 'list';
            $listedPages = $activeCategory->getPages();
            if (count($listedPages) === 1) {
                $activePage = array_shift($listedPages);
                self::$pageType = 'product';
                $listedPages = [];
            }
        } elseif (!empty($_GET['q'])
            || !empty($_GET['varenr'])
            || !empty($_GET['minpris'])
            || !empty($_GET['maxpris'])
            || !empty($_GET['sogikke'])
            || !empty($_GET['maerke'])
        ) {
            // Brand search
            if (empty($_GET['q'])
                && empty($_GET['varenr'])
                && empty($_GET['minpris'])
                && empty($_GET['maxpris'])
                && empty($_GET['sogikke'])
                && !empty($_GET['maerke'])
            ) {
                $maerkeet = db()->fetchOne(
                    "
                    SELECT `id`, `navn`
                    FROM `maerke`
                    WHERE id = " . (int) $_GET['maerke']
                );
                if ($maerkeet) {
                    $redirectUrl = '/mærke' . $maerkeet['id'] . '-' . clearFileName($maerkeet['navn']) . '/';
                    redirect($redirectUrl, 301);
                }
            }

            //Full search
            $where = "";
            $wherekat = "";
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
                $where .= " AND !MATCH (navn, text, beskrivelse) AGAINST('" . db()->esc($_GET['sogikke']) ."') > 0";
                $wherekat .= " AND !MATCH (navn) AGAINST('" . db()->esc($_GET['sogikke']) . "') > 0";
            }
            $listedPages = searchListe($_GET['q'] ?? '', $where);
            if (count($listedPages) === 1) {
                $page = array_shift($listedPages);
                redirect($page->getCanonicalLink(), 302);
            }

            searchMenu($_GET['q'] ?? '', $wherekat);

            $GLOBALS['generatedcontent']['title'] = 'Søg på ' . xhtmlEsc(Config::get('site_name'));
            self::$pageType = 'tiles';
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
                self::addLoadedTable('files');
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
        if (self::$pageType === 'front') {
            $special = db()->fetchOne(
                "
                SELECT text, UNIX_TIMESTAMP(dato) AS dato
                FROM special
                WHERE id = 1
                "
            );
            if ($special['dato']) {
                self::addUpdateTime(strtotime($special['dato']) + db()->getTimeOffset());
            } else {
                self::addLoadedTable('special');
            }

            $GLOBALS['generatedcontent']['text'] = $special['text'];
        } elseif (self::$pageType === 'search') {
            $GLOBALS['generatedcontent']['title'] = 'Søg på ' . xhtmlEsc(Config::get('site_name'));

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
            self::addLoadedTable('maerke');

            foreach ($maerker as $value) {
                $text .= '<option value="'.$value['id'].'">';
                $text .= xhtmlEsc($value['navn']) . '</option>';
            }
            $text .= '</select></td></tr></table></form>';
            $GLOBALS['generatedcontent']['text'] = $text;
        } elseif (self::$pageType === 'product') {
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
            self::addLoadedTable('lists');

            foreach ($lists as $list) {
                $html .= '<div id="table' . $list['id'] . '">';
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
                self::addLoadedTable('krav');

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
                self::addLoadedTable('maerke');

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
        $GLOBALS['generatedcontent']['contenttype'] = self::$pageType;
    }

    public static function outputPage() {
        self::prepareData();
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            ini_set('zlib.output_compression', 0);
            return;
        }

        require_once _ROOT_ . '/theme/index.php';
    }
}
