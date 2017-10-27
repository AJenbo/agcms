<?php namespace AGCMS;

use AGCMS\Entity\AbstractRenderable;
use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\Entity\Table;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;
use Twig_Loader_Filesystem;

class Render
{
    /** @var Brand */
    private static $email = '';
    private static $hasProductList = false;
    private static $searchValues = [];

    private static $loadedTables = [];
    private static $pageList = [];
    private static $searchMenu = [];
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
    public static $crumbs = [];
    public static $bodyHtml = '';

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

        $response = new Response();
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

    /**
     * Prepare data for render.
     */
    public static function prepareData(): void
    {
        $request = request();

        self::$searchValues = $request->get('q') ? ['q' => $request->get('q')] : [];

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
                    assert($brand instanceof Brand);
                    redirect($brand->getCanonicalLink(), Response::HTTP_MOVED_PERMANENTLY);
                }
            } elseif ($request->get('q') && !$request->get('sog')) {
                redirect('/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=', Response::HTTP_MOVED_PERMANENTLY);
            }
        }

        self::$title = self::$title ?: Config::get('site_name');

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
                assert($category instanceof Category);
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
        }
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
            assert($page instanceof Page);
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
            assert($category instanceof Category);
            if ($category->isVisable() && !$category->isInactive()) {
                $searchMenu[] = $category;
            }
        }

        return $searchMenu;
    }

    /**
     * Output the page to the browser.
     */
    public static function outputPage(): void
    {
        session_write_close();
        self::prepareData();

        self::output(
            self::$pageType,
            [
                'hasProductList' => self::$hasProductList,
                'pageList'       => self::$pageList,
                'title'          => self::$title,
                'crumbs'         => self::$crumbs,
                'content'        => self::$bodyHtml,
                'searchMenu'     => self::$searchMenu,
                'hasItemsInCart' => !empty($_SESSION['faktura']['quantities']),
                'search'         => self::$searchValues,
            ]
        );
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
