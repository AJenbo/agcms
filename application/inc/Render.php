<?php

class Render
{
    private static $accessories = [];
    private static $brand = [];
    private static $canonical = '';
    private static $email = '';
    private static $keywords = [];
    private static $loadedTables = [];
    private static $menu = [];
    private static $pageList = [];
    private static $pages = [];
    private static $price = [];
    private static $requirement = [];
    private static $searchMenu = [];
    private static $serial = '';
    private static $timeStamp = 0;
    private static $updateTime = 0;
    public static $activeBrand;
    public static $activeCategory;
    public static $activePage;
    public static $bodyHtml = '';
    public static $crumbs = [];
    public static $has_product_table = false;
    public static $headline = '';
    public static $pageType = 'front';
    public static $title = '';
    public static $track = '';

    public static function doRouting(string $url)
    {
        // Routing
        $brandId = (int) preg_replace('/.*\/mærke([0-9]*)-.*|.*/u', '\1', $url);
        $categoryId = (int) preg_replace('/.*\/kat([0-9]*)-.*|.*/u', '\1', $url);
        $pageId = (int) preg_replace('/.*\/side([0-9]*)-.*|.*/u', '\1', $url);
        $redirect = !$brandId && !$categoryId && !$pageId ? 302 : false;

        if ($brandId) {
            self::$activeBrand = ORM::getOne(Brand::class, $brandId);
            if (!self::$activeBrand) {
                $redirect = 301;
                self::$activeBrand = null;
            }
        }

        if ($categoryId) {
            self::$activeCategory = ORM::getOne(Category::class, $categoryId);
            if (!self::$activeCategory || self::$activeCategory->isInactive()) {
                $redirect = self::$activeCategory ? 302 : 301;
                self::$activeCategory = null;
            }
        }
        if ($pageId) {
            self::$activePage = ORM::getOne(Page::class, $pageId);
            if (!self::$activePage || self::$activePage->isInactive()) {
                $redirect = self::$activePage ? 302 : 301;
                self::$activePage = null;
            }
        }

        if ($redirect) {
            $redirectUrl = '/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=';
            $q = preg_replace(
                [
                    '/\/|-|_|\.html|\.htm|\.php|\.gif|\.jpeg|\.jpg|\.png|mærke[0-9]+-|kat[0-9]+-|side[0-9]+-|\.php/u',
                    '/[^\w0-9]/u',
                    '/([0-9]+)/u',
                    '/([[:upper:]]?[[:lower:]]+)/u',
                    '/\s+/u'
                ],
                [
                    ' ',
                    ' ',
                    ' \1 ',
                    ' \1',
                    ' '
                ],
                $url
            );
            $q = trim($q);
            if ($q) {
                $redirectUrl = '/?q=' . rawurlencode($q) . '&sogikke=&minpris=&maxpris=&maerke=0';
            }
            if (self::$activePage) {
                $redirectUrl = self::$activePage->getCanonicalLink(self::$activeCategory);
            } elseif (self::$activeCategory) {
                $redirectUrl = '/' . self::$activeCategory->getSlug();
            }

            redirect($redirectUrl, $redirect);
        }
    }

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
            $where = "";
            if (self::$loadedTables) {
                $where = " WHERE Name IN('" . implode("', '", array_keys(self::$loadedTables)) . "')";
            }
            $tables = db()->fetchArray("SHOW TABLE STATUS" . $where);
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
        header('Cache-Control: max-age=0, must-revalidate'); // HTTP/1.1
        header('Pragma: no-cache');                          // HTTP/1.0

        if (!empty($_SESSION['faktura']['quantities'])) {
            return;
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
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }
        ini_set('zlib.output_compression', 0);
        header('HTTP/1.1 304 Not Modified', true, 304);
        die();
    }

    public static function prepareData()
    {
        // TODO only grab relevant variables (Especially test custom pages)
        self::$title = Config::get('site_name');
        self::$email = first(Config::get('emails'))['address'];
        if (self::$activeCategory) {
            self::$email = self::$activeCategory->getEmail();
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
        self::addLoadedTable('bind');

        foreach ($pages as $page) {
            self::$pages[] = [
                'id'   => $page->getId(),
                'name' => $page->getTitle(),
                'link' => '/' . $page->getSlug(),
            ];
        }

        $categoryIds = [];
        if (self::$activeCategory) {
            self::$crumbs = [];
            foreach (self::$activeCategory->getBranch() as $category) {
                $categoryIds[] = $category->getId();
                self::$keywords[] = trim($category->getTitle());
                self::$crumbs[] = [
                    'name' => $category->getTitle(),
                    'link' => '/' . $category->getSlug(),
                    'icon' => $category->getIconPath(),
                ];
            };
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
        self::$menu = self::menu($categories, $categoryIds);

        $listedPages = [];
        if (!empty($_GET['sog'])) {
            self::$pageType = 'search';
        } elseif (self::$activeBrand) {
            self::$pageType = 'brand';
            self::$title = self::$activeBrand->getTitle();
            self::$brand = [
                'link'  => '/' . self::$activeBrand->getSlug(),
                'name'  => self::$activeBrand->getTitle(),
                'xlink' => self::$activeBrand->getLink(),
                'icon'  => self::$activeBrand->getIconPath(),
            ];

            $listedPages = [];
            foreach (self::$activeBrand->getPages() as $page) {
                if (!$page->isInactive()) {
                    $listedPages[] = $page;
                }
            }
        } elseif (self::$activePage) {
            self::$pageType = 'product';
        } elseif (self::$activeCategory) {
            self::$pageType = self::$activeCategory->getRenderMode() == Category::GALLERY ? 'tiles' : 'list';
            $listedPages = self::$activeCategory->getPages();
            if (count($listedPages) === 1) {
                self::$activePage = array_shift($listedPages);
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
                $brand = ORM::getOne(Brand::class, $_GET['maerke']);
                if ($brand) {
                    redirect('/' . $brand->getSlug(), 301);
                }
            }

            $listedPages = self::searchListe(
                $_GET['q'] ?? '',
                (int) $_GET['maerke'] ?? 0,
                $_GET['varenr'] ?? '',
                (int) $_GET['minpris'] ?? 0,
                (int) $_GET['maxpris'] ?? 0,
                $_GET['sogikke'] ?? ''
            );
            if (count($listedPages) === 1) {
                $page = array_shift($listedPages);
                redirect($page->getCanonicalLink(), 302);
            }

            self::$searchMenu = self::getSearchMenu(
                $_GET['q'] ?? '',
                $_GET['sogikke'] ?? ''
            );
            self::$title = 'Søg på ' . Config::get('site_name');
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

                if (!self::$activeCategory || self::$activeCategory->getRenderMode() === Category::GALLERY) {
                    self::$pageList[] = [
                        'id' => $page->getId(),
                        'name' => $page->getTitle(),
                        'date' => $page->getTimeStamp(),
                        'link' => $page->getCanonicalLink(self::$activeCategory),
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
                    self::$pageList[] = [
                        'id' => $page->getId(),
                        'name' => $page->getTitle(),
                        'date' => $page->getTimeStamp(),
                        'link' => $page->getCanonicalLink(self::$activeCategory),
                        'serial' => $page->getSku(),
                        'price' => [
                            'before' => $page->getOldPrice(),
                            'now' => $page->getPrice(),
                        ]
                    ];
                }
            }
        }

        if (self::$activeCategory && empty(self::$title)) {
            self::$title = trim(self::$activeCategory->getTitle());

            if (self::$activeCategory->getIconPath()) {
                $icon = db()->fetchOne(
                    "
                    SELECT `alt`
                    FROM `files`
                    WHERE path = '" . db()->esc(self::$activeCategory->getIconPath()) . "'"
                );
                self::addLoadedTable('files');
                if (!empty($icon['alt'])) {
                    self::$title .= (self::$title ? ' ' : '') . $icon['alt'];
                } elseif (!self::$title) {
                    $path = pathinfo(self::$activeCategory->getIconPath());
                    self::$title = ucfirst(preg_replace('/-/ui', ' ', $path['filename']));
                }
            }
        }

        //Get page content and type
        if (self::$pageType === 'front') {
            self::$bodyHtml = ORM::getOne(CustomPage::class, 1)->getHtml();
        } elseif (self::$pageType === 'search') {
            self::$title = 'Søg på ' . Config::get('site_name');

            self::$bodyHtml = '<form action="/" method="get"><table><tr><td>' . _('Contains')
                . '</td><td><input name="q" size="31" /></td><td><input type="submit" value="' . _('Search')
                . '" /></td></tr><tr><td>' . _('Part No.')
                . '</td><td><input name="varenr" size="31" value="" maxlength="63" /></td></tr><tr><td>'
                . _('Without the words') . '</td><td><input name="sogikke" size="31" value="" /></td></tr><tr><td>'
                . _('Min price')
                . '</td><td><input name="minpris" size="5" maxlength="11" value="" />,-</td></tr><tr><td>'
                . _('Max price')
                . '&nbsp;</td><td><input name="maxpris" size="5" maxlength="11" value="" />,-</td></tr><tr><td>'
                . _('Brand:') . '</td><td><select name="maerke"><option value="0">' . _('All') . '</option>';

            $brands = ORM::getOne(Brand::class, "SELECT * FROM `maerke` ORDER BY `navn`");
            foreach ($brands as $brand) {
                self::$bodyHtml .= '<option value="' . $brand->getId() . '">'
                    . xhtmlEsc($brand->getTitle()) . '</option>';
            }
            self::$bodyHtml .= '</select></td></tr></table></form>';
        } elseif (self::$pageType === 'product') {
            self::$canonical = self::$activePage->getCanonicalLink();
            self::$title = self::$activePage->getTitle();
            self::$headline = self::$activePage->getTitle();
            self::$serial = self::$activePage->getSku();
            self::$timeStamp = self::$activePage->getTimestamp();
            self::$price = [
                'now'    => self::$activePage->getPrice(),
                'new'    => self::$activePage->getPrice(),
                'from'   => self::$activePage->getPriceType(),
                'before' => self::$activePage->getOldPrice(),
                'old'    => self::$activePage->getOldPrice(),
                'market' => self::$activePage->getOldPriceType(),
            ];

            self::$bodyHtml = self::$activePage->getHtml();
            $lists = db()->fetchArray(
                "
                SELECT id
                FROM `lists`
                WHERE `page_id` = " . self::$activePage->getId()
            );
            self::addLoadedTable('lists');

            foreach ($lists as $list) {
                self::$bodyHtml .= '<div id="table' . $list['id'] . '">';
                self::$bodyHtml .= getTableHtml($list['id'], null, self::$activeCategory);
                self::$bodyHtml .= '</div>';
            }

            if (self::$activePage->getRequirementId()) {
                $krav = db()->fetchOne(
                    "
                    SELECT id, navn
                    FROM krav
                    WHERE id = " . self::$activePage->getRequirementId()
                );
                self::addLoadedTable('krav');

                self::$requirement = [
                    'icon' => '',
                    'name' => $krav['navn'],
                    'link' => '/krav/' . $krav['id'] . '/' . clearFileName($krav['navn']) . '.html',
                ];
            }

            $brand = self::$activePage->getBrand();
            if ($brand) {
                self::$brand = [
                    'name'  => $brand->getTitle(),
                    'link'  => '/' . $brand->getSlug(),
                    'xlink' => $brand->getLink(),
                    'icon'  => $brand->getIconPath(),
                ];
            }

            foreach (self::$activePage->getAccessories() as $page) {
                self::$accessories[] = [
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

            self::$keywords[] = self::$activePage->getTitle();
        }
    }

    /**
     * Get list of sub categories in format fitting the generatedcontent structure
     *
     * @param array $categories       Categories
     * @param array $categoryIds      Ids in active category trunk
     * @param array $weightedChildren Are the categories the list custome sorted
     *
     * @return array
     */
    public static function menu(array $categories, array $categoryIds, bool $weightedChildren = true): array
    {
        $menu = [];
        if (!$weightedChildren) {
            $objectArray = [];
            foreach ($categories as $categorie) {
                $objectArray[] = [
                    'id'     => $categorie->getId(),
                    'navn'   => $categorie->getTitle(),
                    'object' => $categorie,
                ];
            }
            $objectArray = arrayNatsort($objectArray, 'id', 'navn', 'asc');
            $categories = [];
            foreach ($objectArray as $row) {
                $categories[] = $row['object'];
            }
        }

        foreach ($categories as $category) {
            if (!$category->isVisable()) {
                continue;
            }

            //Er katagorien aaben
            $subs = [];
            if (in_array($category->getId(), $categoryIds, true)) {
                $subs = self::menu(
                    $category->getChildren(true),
                    $categoryIds,
                    $category->getWeightedChildren()
                );
            }


            //tegn under punkter
            $menu[] = [
                'id'   => $category->getId(),
                'name' => $category->getTitle(),
                'link' => '/' . $category->getSlug(),
                'icon' => $category->getIconPath(),
                'sub'  => $subs ? true : $category->hasChildren(true),
                'subs' => $subs,
            ];
        }

        return $menu;
    }

    /**
     * Search for pages and generate a list or redirect if only one was found
     *
     * @param string $q     Tekst to search for
     * @param string $where Additional sql where clause
     *
     * @return null
     */
    public static function searchListe(string $q, int $brandId, string $varenr = '', int $minpris = 0, int $maxpris = 0, string $antiWords)
    {
        $pages = [];

        //Full search
        $where = "";
        if ($brandId) {
            $where = " AND `maerke` = " . $brandId;
        }
        if ($varenr) {
            $where .= " AND varenr LIKE '" . db()->esc($varenr) . "%'";
        }
        if ($minpris) {
            $where .= " AND pris > " . $minpris;
        }
        if ($maxpris) {
            $where .= " AND pris < " . $maxpris;
        }
        if ($antiWords) {
            $where .= " AND !MATCH (navn, text, beskrivelse) AGAINST('" . db()->esc($antiWords) ."') > 0
            AND `navn` NOT LIKE '%$simpleq%'
            AND `text` NOT LIKE '%$simpleq%'
            AND `beskrivelse` NOT LIKE '%$simpleq%'
            ";
        }

        $simpleSearchString = $antiWords ? '%' . preg_replace('/\s+/u', '%', $searchString) . '%' : '';
        $simpleAntiWords = $antiWords ? '%' . preg_replace('/\s+/u', '%', $antiWords) . '%' : '';

        //TODO match on keywords
        $columns = [];
        foreach (db()->fetchArray("SHOW COLUMNS FROM sider") as $column) {
            $columns[] = $column['Field'];
        }
        $simpleq = "%" . preg_replace('/\s+/u', "%", $q) . "%";
        $pages = ORM::getByQuery(
            Page::class,
            "
            SELECT `" . implode("`, `", $columns) . "`
            FROM (SELECT sider.*, MATCH(navn, text, beskrivelse) AGAINST ('" . db()->esc($q) . "') AS score
            FROM sider
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE (
                MATCH (navn, text, beskrivelse) AGAINST('" . db()->esc($q) . "') > 0
                OR `navn` LIKE '%$simpleq%'
                OR `text` LIKE '%$simpleq%'
                OR `beskrivelse` LIKE '%$simpleq%'
            )
            $where
            ORDER BY `score` DESC) x
            UNION
            SELECT sider.* FROM `list_rows`
            JOIN lists ON list_rows.list_id = lists.id
            JOIN sider ON lists.page_id = sider.id
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE list_rows.`cells` LIKE '%$simpleq%'"
            . $where
        );
        Render::addLoadedTable('list_rows');
        Render::addLoadedTable('lists');

        // Remove inactive pages
        foreach ($pages as $key => $page) {
            if ($page->isInactive()) {
                unset($pages[$key]);
            }
        }

        return array_values($pages);
    }

    /**
     * Search for categories and populate generatedcontent with results
     *
     * @param string $searchString Seach string
     * @param string $wherekat     Additional SQL for WHERE clause
     *
     * @return null
     */
    public static function getSearchMenu(string $searchString, string $antiWords): array
    {
        $searchMenu = [];
        if (!$searchString) {
            return $searchMenu;
        }

        $brands = ORM::getByQuery(
            Brand::class,
            "
            SELECT * FROM `maerke`
            WHERE (
                MATCH (navn) AGAINST('" . db()->esc($searchString) . "') > 0
                OR navn LIKE '" . db()->esc($simpleSearchString) . "'
            )
            AND !MATCH (navn) AGAINST('" . db()->esc($antiWords) . "') > 0
            AND navn NOT LIKE '" . db()->esc($simpleAntiWords) . "'
            "
        );
        foreach ($brands as $brand) {
            $searchMenu[] = [
                'id'   => $brand->getId(),
                'name' => $brand->getTitle(),
                'link' => '/' . $brand->getSlug(),
            ];
        }

        $simpleSearchString = $searchString ? '%' . preg_replace('/\s+/u', '%', $searchString) . '%' : '';
        $simpleAntiWords = $antiWords ? '%' . preg_replace('/\s+/u', '%', $antiWords) . '%' : '';
        $categories = ORM::getByQuery(
            Category::class,
            "
            SELECT *, MATCH (navn) AGAINST ('" . db()->esc($searchString) . "') AS score
            FROM kat
            WHERE (
                MATCH (navn) AGAINST('" . db()->esc($searchString) . "') > 0
                OR navn LIKE '" . db()->esc($simpleSearchString) . "'
            )
            AND !MATCH (navn) AGAINST('" . db()->esc($antiWords) . "') > 0
            AND navn NOT LIKE '" . db()->esc($simpleAntiWords) . "'
            AND `vis` != '0'
            ORDER BY score, navn
            "
        );
        foreach ($categories as $category) {
            if ($category->isVisable() && !$category->isInactive()) {
                $searchMenu[] = [
                    'id' => $category->getId(),
                    'name' => $category->getTitle(),
                    'link' => '/' . $category->getSlug(),
                    'icon' => $category->getIconPath(),
                    'sub' => (bool) $category->getChildren(true),
                ];
            }
        }

        return $searchMenu;
    }

    /**
     * Return html for a sorted list
     *
     * @param int      $listid   Id of list
     * @param int      $bycell   What cell to sort by
     * @param Category $category Id of current category
     *
     * @return array
     */
    public static function getTableHtml(int $listid, int $bycell = null, Category $category = null): string
    {
        $html = '';

        $list = db()->fetchOne("SELECT * FROM `lists` WHERE id = " . $listid);
        $rows = db()->fetchArray(
            "
            SELECT *
            FROM `list_rows`
            WHERE `list_id` = " . $listid
        );
        if (!$rows) {
            Render::sendCacheHeader();
            return ['id' => 'table' . $listid, 'html' => $html];
        }

        // Eager load data
        $pageIds = [];
        foreach ($rows as $row) {
            if ($row['link']) {
                $pageIds[] = $row['link'];
            }
        }
        if ($pageIds) {
            $pages = ORM::getByQuery(
                Page::class,
                "
                SELECT * FROM sider WHERE id IN (" . implode(",", $pageIds) . ")
                "
            );
        }

        //Explode sorts
        $list['sorts'] = explode('<', $list['sorts']);
        $list['cells'] = explode('<', $list['cells']);
        $list['cell_names'] = explode('<', $list['cell_names']);

        if (!$bycell && $bycell !== '0') {
            $bycell = $list['sort'];
        }

        //Explode cells
        foreach ($rows as $row) {
            $cells = explode('<', $row['cells']);
            $cells['id'] = $row['id'];
            $cells['link'] = $row['link'];
            $rows_cells[] = $cells;
        }
        $rows = $rows_cells;
        unset($row);
        unset($cells);
        unset($rows_cells);

        //Sort rows
        if ($list['sorts'][$bycell] < 1) {
            $rows = arrayNatsort($rows, 'id', $bycell);
        } else {
            $rows = arrayListsort(
                $rows,
                'id',
                $bycell,
                $list['sorts'][$bycell]
            );
        }

        //unset temp holder for rows

        $html .= '<table class="tabel">';
        if ($list['title']) {
            $html .= '<caption>'.$list['title'].'</caption>';
        }
        $html .= '<thead><tr>';
        foreach ($list['cell_names'] as $key => $cell_name) {
            $html .= '<td><a href="" onclick="x_getTable(\'' . $list['id']
            . '\', \'' . $key . '\', ' . ($category ? $category->getId() : '')
            . ', inject_html);return false;">' . $cell_name . '</a></td>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $i => $row) {
            $html .= '<tr';
            if ($i % 2) {
                $html .= ' class="altrow"';
            }
            $html .= '>';
            if ($row['link']) {
                $page = ORM::getOne(Page::class, $row['link']);
                $row['link'] = '<a href="' . xhtmlEsc($page->getCanonicalLink($category)) . '">';
            }
            foreach ($list['cells'] as $key => $type) {
                if (empty($row[$key])) {
                    $row[$key] = '';
                }

                switch ($type) {
                    case 0:
                        //Plain text
                        $html .= '<td>';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        $html .= $row[$key];
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        break;
                    case 1:
                        //number
                        $html .= '<td style="text-align:right;">';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        $html .= $row[$key];
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        break;
                    case 2:
                        //price
                        $html .= '<td style="text-align:right;" class="Pris">';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        if (is_numeric(@$row[$key])) {
                            $html .= str_replace(
                                ',00',
                                ',-',
                                number_format($row[$key], 2, ',', '.')
                            );
                        } else {
                            $html .= @$row[$key];
                        }
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                            $html .= '</td>';
                            Render::$has_product_table = true;
                        break;
                    case 3:
                        //new price
                        $html .= '<td style="text-align:right;" class="NyPris">';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        if (is_numeric(@$row[$key])) {
                            $html .= str_replace(
                                ',00',
                                ',-',
                                number_format($row[$key], 2, ',', '.')
                            );
                        } else {
                            $html .= @$row[$key];
                        }
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                            $html .= '</td>';
                            Render::$has_product_table = true;
                        break;
                    case 4:
                        //pold price
                        $html .= '<td style="text-align:right;" class="XPris">';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        if (is_numeric(@$row[$key])) {
                            $html .= str_replace(
                                ',00',
                                ',-',
                                number_format($row[$key], 2, ',', '.')
                            );
                        }
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        break;
                    case 5:
                        //image
                        $html .= '<td>';
                        $files = db()->fetchOne(
                            "
                            SELECT *
                            FROM `files`
                            WHERE path = " . $row[$key]
                        );
                        Render::addLoadedTable('files');

                        //TODO make image tag
                        if ($row['link']) {
                            $html .= xhtmlEsc($row['link']);
                        }
                        $html .= '<img src="' . xhtmlEsc($row[$key]) . '" alt="'
                        . xhtmlEsc($files['alt']) . '" title="" width="' . $files['width']
                        . '" height="' . $files['height'] . '" />';
                        if (xhtmlEsc($row['link'])) {
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        break;
                }
            }
            if (Render::$has_product_table) {
                $html .= '<td class="addtocart"><a href="/bestilling/?add_list_item='
                . $row['id'] . '"><img src="/theme/images/cart_add.png" title="'
                . _('Add to shopping cart') . '" alt="+" /></a></td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Get the html for content bellonging to a category
     *
     * @param int  $id   Id of activ category
     * @param bool $sort What column to sort by
     *
     * @return array Apropriate for handeling with javascript function inject_html()
     */
    public function getKatHtml(Category $category, string $sort): array
    {
        if (!in_array($sort, ['navn', 'for', 'pris', 'varenr'])) {
            $sort = 'navn';
        }

        //Get pages list
        $pages = $category->getPages($sort);

        $objectArray = [];
        foreach ($pages as $page) {
            $objectArray[] = [
                'id' => $page->getId(),
                'navn' => $page->getTitle(),
                'for' => $page->getOldPrice(),
                'pris' => $page->getPrice(),
                'varenr' => $page->getSku(),
                'object' => $page,
            ];
        }
        $objectArray = arrayNatsort($objectArray, 'id', $sort);
        $pages = [];
        foreach ($objectArray as $item) {
            $pages[] = $item['object'];
        }

        $html = '<table class="tabel"><thead><tr><td><a href="" onclick="x_getKat(\''
        . $category->getId()
        . '\', \'navn\', inject_html);return false">Titel</a></td><td><a href="" onclick="x_getKat(\''
        . $category->getId()
        . '\', \'for\', inject_html);return false">Før</a></td><td><a href="" onclick="x_getKat(\''
        . $category->getId()
        . '\', \'pris\', inject_html);return false">Pris</a></td><td><a href="" onclick="x_getKat(\''
        . $category->getId()
        . '\', \'varenr\', inject_html);return false">#</a></td></tr></thead><tbody>';

        $isEven = false;
        foreach ($pages as $page) {
            $oldPrice = '';
            if ($page->getOldPrice()) {
                $oldPrice = $page->getOldPrice() . ',-';
            }

            $price = '';
            if ($page->getPrice()) {
                $price = $page->getPrice() . ',-';
            }

            $html .= '<tr' . ($isEven ? ' class="altrow"' : '')
            . '><td><a href="' . xhtmlEsc($page->getCanonicalLink($category)) . '">'
            . xhtmlEsc($page->getTitle())
            . '</a></td><td class="XPris" align="right">' . $oldPrice
            . '</td><td class="Pris" align="right">' . $price
            . '</td><td align="right" style="font-size:11px">'
            . xhtmlEsc($page->getSku()) . '</td></tr>';

            $isEven = !$isEven;
        }
        $html .= '</tbody></table>';

        return $html;
    }

    public static function outputPage()
    {
        self::prepareData();
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', 1);
            }
            ini_set('zlib.output_compression', 0);
            return;
        }

        require_once _ROOT_ . '/theme/index.php';
    }
}
