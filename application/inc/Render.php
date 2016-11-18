<?php

class Render
{
    private static $accessories = [];
    private static $activeBrand;
    private static $activeCategory;
    private static $activePage;
    private static $brand = [];
    private static $canonical = '';
    private static $email = '';
    private static $has_product_table = false;
    private static $keywords = [];
    private static $loadedTables = [];
    private static $menu = [];
    private static $pageList = [];
    private static $price = [];
    private static $requirement = [];
    private static $searchMenu = [];
    private static $serial = '';
    private static $timeStamp = 0;
    private static $adminOnlyTables = [
        'email',
        'emails',
        'fakturas',
        'newsmails',
        'PNL',
        'post',
        'template',
        'users',
    ];

    public static $pageType = 'front';
    public static $title = '';
    public static $headline = '';
    public static $crumbs = [];
    public static $bodyHtml = '';
    public static $track = '';

    public static function doRouting(string $url)
    {
        // Routing
        $brandId = (int) preg_replace('/.*\/mærke([0-9]*)-.*|.*/u', '\1', $url);
        $categoryId = (int) preg_replace('/.*\/kat([0-9]*)-.*|.*/u', '\1', $url);
        $pageId = (int) preg_replace('/.*\/side([0-9]*)-.*|.*/u', '\1', $url);
        $redirect = !$brandId && !$categoryId && !$pageId ? 302 : 0;

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
            if (self::$activePage && !self::$activePage->isInCategory($categoryId)) {
                $redirect = 301;
                self::$activeCategory = null;
            }
            if (!self::$activePage || self::$activePage->isInactive()) {
                $redirect = self::$activePage ? 302 : 301;
                self::$activePage = null;
            }
        }

        self::doRedirects($redirect, $url);
    }

    private static function doRedirects(int $redirect, string $url)
    {
        if (!$redirect) {
            return;
        }

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
        $updateTime = 0;
        foreach (get_included_files() as $filename) {
            $updateTime = max($updateTime, filemtime($filename));
        }

        if ($checkDb) {
            $timeOffset = db()->getTimeOffset();
            $where = " WHERE 1";
            if (self::$adminOnlyTables) {
                $where .= " AND Name NOT IN('" . implode("', '", self::$adminOnlyTables) . "')";
            }
            if (self::$loadedTables) {
                $where .= " AND Name IN('" . implode("', '", array_keys(self::$loadedTables)) . "')";
            }
            $tables = db()->fetchArray("SHOW TABLE STATUS" . $where);
            foreach ($tables as $table) {
                $updateTime = max($updateTime, strtotime($table['Update_time']) + $timeOffset);
            }
        }

        if ($updateTime <= 0) {
            return time();
        }

        return $updateTime;
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
        $timeZone = date_default_timezone_get();
        date_default_timezone_set('GMT');
        $last_modified = mb_substr(date('r', $timestamp), 0, -5) . 'GMT';
        date_default_timezone_set($timeZone);
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
        // Brand only search
        if (empty($_GET['q'])
            && empty($_GET['varenr'])
            && empty($_GET['minpris'])
            && empty($_GET['maxpris'])
            && empty($_GET['sogikke'])
        ) {
            if (!empty($_GET['maerke'])) {
                $brand = ORM::getOne(Brand::class, $_GET['maerke']);
                if ($brand) {
                    redirect('/' . $brand->getSlug(), 301);
                }
            } elseif (isset($_GET['q']) && empty($_GET['sog'])) {
                redirect('/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=', 301);
            }
        }

        self::$email = first(Config::get('emails'))['address'];
        self::$title = self::$title ?: Config::get('site_name');

        $categoryIds = [];
        if (self::$activeCategory) {
            self::$crumbs = [];
            foreach (self::$activeCategory->getBranch() as $category) {
                $categoryIds[] = $category->getId();
                self::$keywords[] = trim($category->getTitle());
                self::$crumbs[] = [
                    'name' => $category->getTitle(),
                    'link' => '/' . $category->getSlug(),
                    'icon' => $category->getIcon() ? $category->getIcon()->getPath() : '',
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

        self::loadBrandData(self::$activeBrand);
        self::loadCategoryData(self::$activeCategory);
        self::loadPageData(self::$activePage);

        if (!empty($_GET['sog'])) {
            self::$pageType = 'search';
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

            $categoryIds = [0];
            $categories = ORM::getByQuery(Category::class, "SELECT * FROM kat");
            foreach ($categories as $category) {
                if ($category->isInactive()) {
                    continue;
                }
                $categoryIds[] = $category->getId();
            }
            $brands = ORM::getByQuery(
                Brand::class,
                "
                SELECT * FROM `maerke`
                WHERE id IN(
                    SELECT DISTINCT sider.maerke FROM bind
                    JOIN sider ON sider.id = bind.side
                    WHERE bind.kat IN(" . implode(",", $categoryIds) . ")
                ) ORDER BY `navn`
                "
            );
            foreach ($brands as $brand) {
                self::$bodyHtml .= '<option value="' . $brand->getId() . '">'
                    . xhtmlEsc($brand->getTitle()) . '</option>';
            }
            self::$bodyHtml .= '</select></td></tr></table></form>';
        } elseif (isset($_GET['q'])
            || !empty($_GET['varenr'])
            || !empty($_GET['minpris'])
            || !empty($_GET['maxpris'])
            || !empty($_GET['sogikke'])
            || !empty($_GET['maerke'])
        ) {
            $pages = self::searchListe(
                $_GET['q'] ?? '',
                intval($_GET['maerke'] ?? 0),
                $_GET['varenr'] ?? '',
                intval($_GET['minpris'] ?? 0),
                intval($_GET['maxpris'] ?? 0),
                $_GET['sogikke'] ?? ''
            );
            if (count($pages) === 1) {
                $page = array_shift($pages);
                redirect($page->getCanonicalLink(), 302);
            }
            self::loadPagesData($pages);

            self::$pageType = 'tiles';
            self::$title = 'Søg på ' . Config::get('site_name');
            self::$searchMenu = self::getSearchMenu(
                $_GET['q'] ?? '',
                $_GET['sogikke'] ?? ''
            );
        } elseif (self::$pageType === 'front') {
            self::$bodyHtml = ORM::getOne(CustomPage::class, 1)->getHtml();
        }

        self::cleanData();
    }

    private static function cleanData()
    {
        self::$keywords = array_filter(self::$keywords);
    }

    private static function loadBrandData(Brand $brand = null)
    {
        if (!$brand) {
            return;
        }

        self::$pageType = 'tiles';
        self::$canonical = '/' . $brand->getSlug();
        self::$title = $brand->getTitle();
        self::$brand = [
            'link'  => '/' . $brand->getSlug(),
            'name'  => $brand->getTitle(),
            'xlink' => $brand->getLink(),
            'icon'  => $brand->getIcon() ? $brand->getIcon()->getPath() : '',
        ];

        $pages = [];
        foreach ($brand->getPages() as $page) {
            if (!$page->isInactive()) {
                $pages[] = $page;
            }
        }
        self::loadPagesData($pages);
    }

    private static function loadCategoryData(Category $category = null)
    {
        if (!$category) {
            return;
        }

        $pages = [];
        foreach ($category->getPages() as $page) {
            if (!$page->isInactive()) {
                $pages[] = $page;
            }
        }
        if (count($pages) === 1) {
            self::$activePage = array_shift($pages);
            return;
        }
        self::loadPagesData($pages);

        $title = trim($category->getTitle());
        if ($category->getIcon()) {
            $title = ($title ? ' ' : '') . $category->getIcon()->getDescription();
            if (!$title) {
                $title = pathinfo($category->getIcon() ? $category->getIcon()->getPath() : '', PATHINFO_FILENAME);
                $title = trim(ucfirst(preg_replace('/-/ui', ' ', $title)));
            }
        }
        self::$title     = $title ?: self::$title;
        self::$email     = $category->getEmail();
        self::$canonical = '/' . $category->getSlug();
        self::$pageType  = $category->getRenderMode() === Category::GALLERY ? 'tiles' : 'list';
    }

    private static function loadPagesData(array $pages = null)
    {
        if (!$pages) {
            return;
        }

        $pageArray = [];
        foreach ($pages as $page) {
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

    private static function loadPageData(Page $page = null)
    {
        if (!$page) {
            return;
        }

        self::$pageType   = 'product';
        self::$canonical  = $page->getCanonicalLink();
        self::$headline   = $page->getTitle();
        self::$keywords[] = $page->getTitle();
        self::$serial     = $page->getSku();
        self::$timeStamp  = $page->getTimestamp();
        self::$title      = trim($page->getTitle()) ?: self::$title;

        self::$bodyHtml = $page->getHtml();
        foreach ($page->getTables() as $table) {
            self::$bodyHtml .= '<div id="table' . $table->getId() . '">'
                . self::getTableHtml($table->getId(), null, self::$activeCategory) . '</div>';
        }

        self::$price = [
            'now'    => $page->getPrice(),
            'new'    => $page->getPrice(),
            'from'   => $page->getPriceType(),
            'before' => $page->getOldPrice(),
            'old'    => $page->getOldPrice(),
            'market' => $page->getOldPriceType(),
        ];

        $brand = $page->getBrand();
        if ($brand) {
            self::$brand = [
                'name'  => $brand->getTitle(),
                'link'  => '/' . $brand->getSlug(),
                'xlink' => $brand->getLink(),
                'icon'  => $brand->getIcon() ? $brand->getIcon()->getPath() : '',
            ];
        }

        foreach ($page->getAccessories() as $accessory) {
            self::$accessories[] = [
                'name' => $accessory->getTitle(),
                'link' => $accessory->getCanonicalLink(),
                'icon' => $accessory->getImagePath(),
                'text' => $accessory->getExcerpt(),
                'price' => [
                    'now' => $accessory->getPrice(),
                    'from' => $accessory->getPriceType(),
                    'before' => $accessory->getOldPrice(),
                    'market' => $accessory->getOldPriceType(),
                ],
            ];
        }

    }

    private static function getRootPages(): array
    {
        $return = [];
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
            $return[] = [
                'id'   => $page->getId(),
                'name' => $page->getTitle(),
                'link' => '/' . $page->getSlug(),
            ];
        }

        return $return;
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
                'icon' => $category->getIcon() ? $category->getIcon()->getPath() : '',
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
    public static function searchListe(
        string $q,
        int $brandId,
        string $varenr = '',
        int $minpris = 0,
        int $maxpris = 0,
        string $antiWords = ''
    ) {
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
        self::addLoadedTable('list_rows');
        self::addLoadedTable('lists');

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

        $simpleSearchString = $searchString ? '%' . preg_replace('/\s+/u', '%', $searchString) . '%' : '';
        $simpleAntiWords = $antiWords ? '%' . preg_replace('/\s+/u', '%', $antiWords) . '%' : '';

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
                    'icon' => $category->getIcon() ? $category->getIcon()->getPath() : '',
                    'sub' => (bool) $category->getChildren(true),
                ];
            }
        }

        return $searchMenu;
    }

    /**
     * Return html for a sorted list
     *
     * @param int      $tableId  Id of list
     * @param int      $orderBy  What column to sort by
     * @param Category $category Id of current category
     *
     * @return array
     */
    public static function getTableHtml(int $tableId, int $orderBy = null, Category $category = null): string
    {
        $table = ORM::getOne(Table::class, $tableId);
        if (!$table || !$rows = $table->getRows()) {
            return '';
        }
        $columns = $table->getColumns();

        if ($orderBy === null) {
            $orderBy = (int) $table->getOrderBy();
        }

        // Eager load data
        $pageIds = [];
        foreach ($rows as $row) {
            if ($row['link']) {
                $pageIds[] = $row['link'];
            }
        }
        if ($pageIds) {
            ORM::getByQuery(
                Page::class,
                "
                SELECT * FROM sider WHERE id IN(" . implode(",", $pageIds) . ")
                "
            );
        }

        //Sort rows
        $orderBy = min($orderBy, count($columns) - 1);
        if (empty($columns[$orderBy]['sorting'])) {
            $rows = arrayNatsort($rows, 'id', $orderBy);
        } else {
            $rows = arrayListsort(
                $rows,
                'id',
                $orderBy,
                $columns[$orderBy]['sorting']
            );
        }

        $html = '<table class="tabel">';
        if ($table->getTitle()) {
            $html .= '<caption>' . xhtmlEsc($table->getTitle()) . '</caption>';
        }
        $html .= '<thead><tr>';
        foreach ($columns as $columnId => $column) {
            if (in_array($column['type'], [Table::COLUMN_TYPE_PRICE, Table::COLUMN_TYPE_PRICE_NEW], true)) {
                self::$has_product_table = true;
            }

            $html .= '<td><a href="" onclick="x_getTable(' . $table->getId()
            . ', ' . $columnId . ', ' . ($category ? $category->getId() : '0')
            . ', inject_html);return false;">' . xhtmlEsc($column['title']) . '</a></td>';
        }
        if (self::$has_product_table) {
            $html .= '<td></td>';
        }
        $html .= '</tr></thead><tbody>';

        $altRow = false;
        foreach ($rows as $row) {
            $html .= '<tr';
            if ($altRow) {
                $html .= ' class="altrow"';
            }
            $altRow = !$altRow;
            $html .= '>';

            $linkTag = '';
            $page = null;
            if ($row['link']) {
                $page = ORM::getOne(Page::class, $row['link']);
                $linkTag = '<a href="' . xhtmlEsc($page->getCanonicalLink($category)) . '">';
            }
            foreach ($columns as $columnId => $column) {
                switch ($column['type']) {
                    case Table::COLUMN_TYPE_STRING:
                        $html .= '<td>';
                        break;
                    case Table::COLUMN_TYPE_INT:
                        $html .= '<td style="text-align:right;">';
                        break;
                    case Table::COLUMN_TYPE_PRICE:
                        $html .= '<td style="text-align:right;" class="Pris">';
                        break;
                    case Table::COLUMN_TYPE_PRICE_NEW:
                        $html .= '<td style="text-align:right;" class="NyPris">';
                        break;
                    case Table::COLUMN_TYPE_PRICE_OLD:
                        $html .= '<td style="text-align:right;" class="XPris">';
                        break;
                }

                if ($linkTag) {
                    $html .= $linkTag;
                }

                switch ($column['type']) {
                    case Table::COLUMN_TYPE_STRING:
                    case Table::COLUMN_TYPE_INT:
                        $html .= xhtmlEsc($row[$columnId]);
                        break;
                    case Table::COLUMN_TYPE_PRICE:
                    case Table::COLUMN_TYPE_PRICE_NEW:
                    case Table::COLUMN_TYPE_PRICE_OLD:
                        if ($row[$columnId]) {
                            $html .= str_replace(',00', ',-', number_format($row[$columnId], 2, ',', '.'));
                        }
                        break;
                }
                if ($linkTag) {
                    $html .= '</a>';
                }
                $html .= '</td>';
            }
            if (self::$has_product_table) {
                $html .= '<td class="addtocart"><a href="/bestilling/?'
                . ($page ? ('add=' . $page->getId()) : ('add_list_item=' . $row['id']))
                . '"><img src="/theme/images/cart_add.png" title="'
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
     * @return string
     */
    public static function getKatHtml(Category $category, string $sort): string
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
