<?php namespace AGCMS;

use AGCMS\Entity\AbstractRenderable;
use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\Entity\Table;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;
use Twig_Loader_Filesystem;

class Render
{
    /** @var Requirement */
    private static $activeRequirement;
    /** @var Brand */
    private static $activeBrand;
    /** @var Category */
    private static $activeCategory;

    /** @var Page */
    private static $activePage;
    /** @var Brand */
    private static $brand;
    private static $canonical = '';
    private static $email = '';
    private static $hasProductList = false;
    private static $keywords = [];
    private static $searchValues = [];

    /** @var Response */
    private static $response;

    private static $loadedTables = [];
    private static $menu = [];
    private static $openCategoryIds = [];
    private static $pageList = [];
    private static $price = [];
    private static $searchMenu = [];
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

    public static $pageType = 'index';
    public static $title = '';
    public static $headline = '';
    public static $crumbs = [];
    public static $bodyHtml = '';
    public static $track = '';

    /**
     * Do routing.
     */
    public static function doRouting(): void
    {
        $url = urldecode(request()->getRequestUri());
        self::makeUrlUtf8($url);

        // Routing
        $requirementId = (int) preg_replace('/\/krav\/([0-9]*)\/.*/u', '\1', $url);
        $brandId = (int) preg_replace('/.*\/mærke([0-9]*)-.*|.*/u', '\1', $url);
        $categoryId = (int) preg_replace('/.*\/kat([0-9]*)-.*|.*/u', '\1', $url);
        $pageId = (int) preg_replace('/.*\/side([0-9]*)-.*|.*/u', '\1', $url);
        $redirect = !$brandId && !$categoryId && !$pageId && !$requirementId ? Response::HTTP_FOUND : 0;

        if ($requirementId) {
            self::$activeRequirement = ORM::getOne(Requirement::class, $requirementId);
            if (!self::$activeRequirement) {
                $redirect = Response::HTTP_MOVED_PERMANENTLY;
                self::$activeRequirement = null;
            }
        }

        if ($brandId) {
            self::$activeBrand = ORM::getOne(Brand::class, $brandId);
            if (!self::$activeBrand) {
                $redirect = Response::HTTP_MOVED_PERMANENTLY;
                self::$activeBrand = null;
            }
        }

        if ($categoryId) {
            self::$activeCategory = ORM::getOne(Category::class, $categoryId);
            if (!self::$activeCategory || self::$activeCategory->isInactive()) {
                $redirect = self::$activeCategory ? Response::HTTP_FOUND : Response::HTTP_MOVED_PERMANENTLY;
                self::$activeCategory = null;
            }
        }
        if ($pageId) {
            self::$activePage = ORM::getOne(Page::class, $pageId);
            if (self::$activePage && self::$activeCategory && !self::$activePage->isInCategory(self::$activeCategory)) {
                $redirect = Response::HTTP_MOVED_PERMANENTLY;
                self::$activeCategory = null;
            }
            if (!self::$activePage || self::$activePage->isInactive()) {
                $redirect = self::$activePage ? Response::HTTP_FOUND : Response::HTTP_MOVED_PERMANENTLY;
                self::$activePage = null;
            }
        }

        self::doRedirects($redirect, $url);
    }

    /**
     * Make sure URL is UTF8 and redirect if nessesery.
     *
     * @param string $url Requested url
     */
    private static function makeUrlUtf8(string $url): void
    {
        $encoding = mb_detect_encoding($url, 'UTF-8, ISO-8859-1');
        if ('UTF-8' !== $encoding) {
            // Windows-1252 is a superset of iso-8859-1
            if (!$encoding || 'ISO-8859-1' == $encoding) {
                $encoding = 'windows-1252';
            }
            $url = mb_convert_encoding($url, 'UTF-8', $encoding);
            redirect($url, Response::HTTP_MOVED_PERMANENTLY);
        }
    }

    /**
     * Do redirects for routing.
     *
     * @param int    $redirect redirect code
     * @param string $url      Requested url
     */
    private static function doRedirects(int $redirect, string $url): void
    {
        if (!$redirect) {
            return;
        }

        $redirectUrl = '/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=';
        $query = preg_replace(
            [
                '/\/|-|_|\.html|\.htm|\.php|\.gif|\.jpeg|\.jpg|\.png|mærke[0-9]+-|kat[0-9]+-|side[0-9]+-|\.php/u',
                '/[^\w0-9]/u',
                '/([0-9]+)/u',
                '/([[:upper:]]?[[:lower:]]+)/u',
                '/\s+/u',
            ],
            [
                ' ',
                ' ',
                ' \1 ',
                ' \1',
                ' ',
            ],
            $url
        );
        $query = trim($query);
        if ($query) {
            $redirectUrl = '/?q=' . rawurlencode($query) . '&sogikke=&minpris=&maxpris=&maerke=0';
        }
        if (self::$activePage) {
            $redirectUrl = self::$activePage->getCanonicalLink(self::$activeCategory);
        } elseif (self::$activeCategory) {
            $redirectUrl = self::$activeCategory->getCanonicalLink();
        } elseif (self::$activeBrand) {
            $redirectUrl = self::$activeBrand->getCanonicalLink();
        } elseif (self::$activeRequirement) {
            $redirectUrl = self::$activeRequirement->getCanonicalLink();
        }

        redirect($redirectUrl, $redirect);
    }

    /**
     * Remember what tabels where read during page load.
     *
     * @param string $tableName The table name
     */
    public static function addLoadedTable(string $tableName): void
    {
        self::$loadedTables[$tableName] = true;
    }

    /**
     * Figure out when the data for this page was last touched.
     */
    public static function getUpdateTime(bool $checkDb = true): int
    {
        $updateTime = 0;
        foreach (get_included_files() as $filename) {
            $updateTime = max($updateTime, filemtime($filename));
        }

        if ($checkDb) {
            $updateTime = self::checkDbUpdate($updateTime);
        }

        if ($updateTime <= 0) {
            return time();
        }

        return $updateTime;
    }

    private static function checkDbUpdate(int $updateTime): int
    {
        $timeOffset = db()->getTimeOffset();
        $where = ' WHERE 1';
        if (self::$adminOnlyTables) {
            $where .= " AND Name NOT IN('" . implode("', '", self::$adminOnlyTables) . "')";
        }
        if (self::$loadedTables) {
            $where .= " AND Name IN('" . implode("', '", array_keys(self::$loadedTables)) . "')";
        }
        $tables = db()->fetchArray('SHOW TABLE STATUS' . $where);
        foreach ($tables as $table) {
            $updateTime = max($updateTime, strtotime($table['Update_time']) + $timeOffset);
        }

        return $updateTime;
    }

    /**
     * Set Last-Modified and ETag http headers
     * and use cache if no updates since last visit.
     *
     * @param int|null $timestamp Unix time stamp of last update to content
     */
    public static function sendCacheHeader(int $timestamp = null): void
    {
        if (!request()->isMethodCacheable() || !empty($_SESSION['faktura']['quantities'])) {
            return;
        }

        if (!$timestamp) {
            $timestamp = self::getUpdateTime();
        }
        if (!$timestamp) {
            return;
        }

        $response = self::getResponse();
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');

        $lastModified = DateTime::createFromFormat('U', (string) $timestamp);
        $response->setLastModified($lastModified);
        $response->setEtag((string) $timestamp);
        $response->setMaxAge(0);

        if ($response->isNotModified(request())) {
            $response->send();
            exit;
        }
    }

    private static function getResponse(): Response
    {
        if (!self::$response) {
            self::$response = new Response();
        }

        return self::$response;
    }

    /**
     * Prepare data for render.
     */
    public static function prepareData(): void
    {
        $request = request();

        self::$searchValues = ['q' => $request->get('q')];

        array_unshift(self::$crumbs, ORM::getOne(Category::class, 0));

        // Brand only search
        if (!$request->get('q')
            && !$request->get('varenr')
            && !$request->get('minpris')
            && !$request->get('maxpris')
            && !$request->get('sogikke')
        ) {
            if ($request->get('maerke')) {
                $brand = ORM::getOne(Brand::class, $request->get('maerke'));
                if ($brand) {
                    redirect($brand->getCanonicalLink(), Response::HTTP_MOVED_PERMANENTLY);
                }
            } elseif ($request->get('q') && !$request->get('sog')) {
                redirect('/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=', Response::HTTP_MOVED_PERMANENTLY);
            }
        }

        self::$email = first(Config::get('emails'))['address'];
        self::$title = self::$title ?: Config::get('site_name');

        if (self::$activeCategory) {
            self::$crumbs = self::$activeCategory->getBranch();
            foreach (self::$crumbs as $category) {
                self::$openCategoryIds[] = $category->getId();
                self::$keywords[] = trim($category->getTitle());
            }
        }

        //Get list of top categorys on the site.
        self::addLoadedTable('bind');
        self::$menu = ORM::getByQuery(
            Category::class,
            '
            SELECT *
            FROM `kat`
            WHERE kat.vis != ' . Category::HIDDEN . '
                AND kat.bind = 0
                AND (id IN (SELECT bind FROM kat WHERE vis != ' . Category::HIDDEN . ')
                    OR id IN (SELECT kat FROM bind)
                )
            ORDER BY `order`, navn
            '
        );

        self::loadBrandData(self::$activeBrand);
        self::loadCategoryData(self::$activeCategory);
        self::loadPageData(self::$activePage);

        if ($request->get('sog')) {
            self::$crumbs[] = [
                'canonicalLink' => '/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=',
                'title' => _('Search'),
            ];
            self::$pageType = 'search';
            self::$title = 'Søg på ' . Config::get('site_name');

            $categoryIds = [0];
            $categories = ORM::getByQuery(Category::class, 'SELECT * FROM kat');
            foreach ($categories as $category) {
                if ($category->isInactive()) {
                    continue;
                }
                $categoryIds[] = $category->getId();
            }
            $brands = ORM::getByQuery(
                Brand::class,
                '
                SELECT * FROM `maerke`
                WHERE id IN(
                    SELECT DISTINCT sider.maerke FROM bind
                    JOIN sider ON sider.id = bind.side
                    WHERE bind.kat IN(' . implode(',', $categoryIds) . ')
                ) ORDER BY `navn`
                '
            );
            $request = request();
            self::$searchValues = [
                'q'       => $request->get('q'),
                'varenr'  => $request->get('varenr'),
                'minpris' => $request->get('minpris'),
                'maxpris' => $request->get('maxpris'),
                'sogikke' => $request->get('sogikke'),
                'maerke'  => $request->get('maerke'),
                'brands'  => $brands,
            ];
        } elseif ($request->get('q')
            || $request->get('varenr')
            || $request->get('minpris')
            || $request->get('maxpris')
            || $request->get('sogikke')
            || $request->get('maerke')
        ) {
            self::$crumbs[] = [
                'canonicalLink' => '/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=',
                'title' => _('Search'),
            ];
            self::$pageList = self::searchListe(
                $request->get('q', ''),
                (int) $request->get('maerke', 0),
                $request->get('varenr', ''),
                (int) $request->get('minpris', 0),
                (int) $request->get('maxpris', 0),
                $request->get('sogikke', '')
            );
            if (1 === count(self::$pageList)) {
                $page = array_shift(self::$pageList);
                redirect($page->getCanonicalLink(), Response::HTTP_FOUND);
            }

            self::$pageType = 'tiles';
            self::$title = 'Søg på ' . Config::get('site_name');
            self::$searchMenu = self::getSearchMenu(
                $request->get('q', ''),
                $request->get('sogikke', '')
            );
        } elseif (self::$activeRequirement) {
            self::$pageType = 'requirement';
            self::$title = self::$activeRequirement->getTitle();
            self::$bodyHtml = self::$activeRequirement->getHtml();
            self::$crumbs[] = self::$activeRequirement;
        } elseif ('index' === self::$pageType) {
            self::$bodyHtml = ORM::getOne(CustomPage::class, 1)->getHtml();
        }

        self::cleanData();
    }

    /**
     * Clean gathered data.
     */
    private static function cleanData(): void
    {
        self::$keywords = array_filter(self::$keywords);
    }

    /**
     * Load data from a brand.
     */
    private static function loadBrandData(Brand $brand = null): void
    {
        if (!$brand) {
            return;
        }

        self::$pageType = 'tiles';
        self::$canonical = $brand->getCanonicalLink();
        self::$title = $brand->getTitle();
        self::$brand = $brand;
        self::$crumbs[] = $brand;

        foreach ($brand->getPages() as $page) {
            if (!$page->isInactive()) {
                self::$pageList[] = $page;
            }
        }
    }

    /**
     * Load data from a category.
     */
    private static function loadCategoryData(Category $category = null): void
    {
        if (!$category) {
            return;
        }

        foreach ($category->getPages() as $page) {
            if (!$page->isInactive()) {
                self::$pageList[] = $page;
            }
        }
        if (1 === count(self::$pageList)) {
            self::$activePage = array_shift(self::$pageList);

            return;
        }

        $title = trim($category->getTitle());
        if ($category->getIcon()) {
            $title = ($title ? ' ' : '') . $category->getIcon()->getDescription();
            if (!$title) {
                $title = pathinfo($category->getIcon() ? $category->getIcon()->getPath() : '', PATHINFO_FILENAME);
                $title = trim(ucfirst(preg_replace('/-/ui', ' ', $title)));
            }
        }
        self::$title = $title ?: self::$title;
        self::$email = $category->getEmail();
        self::$canonical = $category->getCanonicalLink();
        self::$pageType = Category::GALLERY === $category->getRenderMode() ? 'tiles' : 'list';
    }

    /**
     * Load data from a page.
     */
    private static function loadPageData(Page $page = null): void
    {
        if (!$page) {
            return;
        }

        self::$pageType = 'product';
        self::$canonical = $page->getCanonicalLink();
        self::$headline = $page->getTitle();
        self::$keywords[] = $page->getTitle();
        self::$timeStamp = $page->getTimestamp();
        self::$title = trim($page->getTitle()) ?: self::$title;

        self::$bodyHtml = $page->getHtml();
        foreach ($page->getTables() as $table) {
            self::$bodyHtml .= '<div id="table' . $table->getId() . '">'
                . self::getTableHtml($table->getId(), null, self::$activeCategory) . '</div>';
        }

        self::$brand = $page->getBrand();
    }

    /**
     * Search for pages and generate a list or redirect if only one was found.
     *
     * @return Page[]
     */
    public static function searchListe(
        string $queryuery,
        int $brandId,
        string $varenr = '',
        int $minpris = 0,
        int $maxpris = 0,
        string $antiWords = ''
    ): array {
        $pages = [];
        $simpleQuery = '%' . preg_replace('/\s+/u', '%', $queryuery) . '%';

        //Full search
        $where = '';
        if ($brandId) {
            $where = ' AND `maerke` = ' . $brandId;
        }
        if ($varenr) {
            $where .= " AND varenr LIKE '" . db()->esc($varenr) . "%'";
        }
        if ($minpris) {
            $where .= ' AND pris > ' . $minpris;
        }
        if ($maxpris) {
            $where .= ' AND pris < ' . $maxpris;
        }
        if ($antiWords) {
            $where .= " AND !MATCH (navn, text, beskrivelse) AGAINST('" . db()->esc($antiWords) . "') > 0
            AND `navn` NOT LIKE '%$simpleQuery%'
            AND `text` NOT LIKE '%$simpleQuery%'
            AND `beskrivelse` NOT LIKE '%$simpleQuery%'
            ";
        }

        //TODO match on keywords
        $columns = [];
        foreach (db()->fetchArray('SHOW COLUMNS FROM sider') as $column) {
            $columns[] = $column['Field'];
        }
        $pages = ORM::getByQuery(
            Page::class,
            '
            SELECT `' . implode('`, `', $columns) . "`
            FROM (SELECT sider.*, MATCH(navn, text, beskrivelse) AGAINST ('" . db()->esc($queryuery) . "') AS score
            FROM sider
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE (
                MATCH (navn, text, beskrivelse) AGAINST('" . db()->esc($queryuery) . "') > 0
                OR `navn` LIKE '%$simpleQuery%'
                OR `text` LIKE '%$simpleQuery%'
                OR `beskrivelse` LIKE '%$simpleQuery%'
            )
            $where
            ORDER BY `score` DESC) x
            UNION
            SELECT sider.* FROM `list_rows`
            JOIN lists ON list_rows.list_id = lists.id
            JOIN sider ON lists.page_id = sider.id
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE list_rows.`cells` LIKE '%$simpleQuery%'"
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
     * Search for categories and populate generatedcontent with results.
     *
     * @return AbstractRenderable[]
     */
    public static function getSearchMenu(string $searchString, string $antiWords): array
    {
        $searchMenu = [];
        if (!$searchString) {
            return $searchMenu;
        }

        $simpleSearchString = $searchString ? '%' . preg_replace('/\s+/u', '%', $searchString) . '%' : '';
        $simpleAntiWords = $antiWords ? '%' . preg_replace('/\s+/u', '%', $antiWords) . '%' : '';

        $searchMenu = ORM::getByQuery(
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
                $searchMenu[] = $category;
            }
        }

        return $searchMenu;
    }

    /**
     * Return html for a sorted list.
     *
     * @param int           $tableId  Id of list
     * @param int|null      $orderBy  What column to sort by
     * @param Category|null $category Current category
     */
    public static function getTableHtml(int $tableId, int $orderBy = null, Category $category = null): string
    {
        $table = ORM::getOne(Table::class, $tableId);
        if (!$table || !$rows = $table->getRows($orderBy)) {
            return '';
        }
        $columns = $table->getColumns();

        if (null === $orderBy) {
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
            ORM::getByQuery(Page::class, 'SELECT * FROM sider WHERE id IN(' . implode(',', $pageIds) . ')');
        }

        $html = '<table class="tabel">';
        if ($table->getTitle()) {
            $html .= '<caption>' . xhtmlEsc($table->getTitle()) . '</caption>';
        }
        $html .= '<thead><tr>';
        foreach ($columns as $columnId => $column) {
            if (in_array($column['type'], [Table::COLUMN_TYPE_PRICE, Table::COLUMN_TYPE_PRICE_NEW], true)) {
                self::$hasProductList = true;
            }

            $html .= '<td><a href="" onclick="x_getTable(' . $table->getId()
            . ', ' . $columnId . ', ' . ($category ? $category->getId() : '0')
            . ', inject_html);return false;">' . xhtmlEsc($column['title']) . '</a></td>';
        }
        if (self::$hasProductList) {
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
                        if ($row[$columnId] >= 0) {
                            $html .= str_replace(',00', ',-', number_format($row[$columnId], 2, ',', '.'));
                        } else {
                            $html .= xhtmlEsc(_('Sold out'));
                        }
                        break;
                }
                if ($linkTag) {
                    $html .= '</a>';
                }
                $html .= '</td>';
            }
            if (self::$hasProductList) {
                $html .= '<td class="addtocart">';
                if ($row[$columnId] >= 0) {
                    $html .= '<a href="/bestilling/?'
                        . ($page ? ('add=' . $page->getId()) : ('add_list_item=' . $row['id']))
                        . '"><img src="/theme/default/images/cart_add.png" title="'
                        . _('Add to shopping cart') . '" alt="+" /></a>';
                }
                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Get the html for content bellonging to a category.
     *
     * @param Category $category Activ category
     * @param string   $sort     What column to sort by
     *
     * @return string
     */
    public static function getKatHtml(Category $category, string $sort): string
    {
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
        $pages = $category->getPages($sort);
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

    /**
     * Output the page to the browser.
     */
    public static function outputPage(): void
    {
        self::prepareData();

        self::output(
            self::$pageType,
            [
                'brand'           => self::$brand,
                'hasProductList'  => self::$hasProductList,
                'price'           => self::$price,
                'pageList'        => self::$pageList,
                'title'           => self::$title,
                'canonical'       => self::$canonical,
                'keywords'        => self::$keywords,
                'crumbs'          => self::$crumbs,
                'content'         => self::$bodyHtml,
                'categoryId'      => self::$activeCategory ? self::$activeCategory->getId() : 0,
                'pageId'          => self::$activePage ? self::$activePage->getId() : 0,
                'headline'        => self::$headline,
                'timeStamp'       => self::$timeStamp,
                'page'            => self::$activePage,
                'menu'            => self::$menu,
                'openCategoryIds' => self::$openCategoryIds,
                'searchMenu'      => self::$searchMenu,
                'hasItemsInCart'  => !empty($_SESSION['faktura']['quantities']),
                'infoPage'        => ORM::getOne(CustomPage::class, 2),
                'rootPages'       => 'index' === self::$pageType ? ORM::getOne(Category::class, 0)->getPages() : [],
                'search'          => self::$searchValues,
            ]
        );
    }

    /**
     * Output the page to the browser.
     */
    public static function output(string $template = 'index', array $data = []): void
    {
        $response = self::getResponse();
        $response->setContent(self::render($template, $data));
        $response->isNotModified(request()); // Set up 304 response if relevant
        $response->send();
    }

    public static function render(string $template = 'index', array $data = []): string
    {
        $templatePath = _ROOT_ . '/theme/';
        $loader = new Twig_Loader_Filesystem('default/', $templatePath);
        if ('en_US' !== Config::get('locale', 'en_US')) {
            $loader->prependPath('default/' . Config::get('locale') . '/');
        }
        if (Config::get('theme')) {
            $loader->prependPath(Config::get('theme') . '/');
            if ('en_US' !== Config::get('locale', 'en_US')) {
                $loader->prependPath(Config::get('theme') . '/' . Config::get('locale') . '/');
            }
        }

        $twig = new Twig_Environment($loader);
        if ('production' === Config::get('enviroment', 'develop')) {
            $twig->setCache(_ROOT_ . '/theme/cache/twig');
        }
        if ('develop' === Config::get('enviroment', 'develop')) {
            $twig->enableDebug();
        }

        return $twig->render($template . '.html', $data);
    }
}
