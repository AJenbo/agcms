<?php

namespace App\Http\Controllers;

use App\Http\Request;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\VolatilePage;
use App\Services\DbService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class Search extends Base
{
    /**
     * Show the advanced search form.
     */
    public function index(): Response
    {
        $data = $this->basicPageData();
        $crumbs = $data['crumbs'] ?? null;
        if (!is_array($crumbs)) {
            $crumbs = [];
        }
        $crumbs[] = new VolatilePage(_('Search'), '/search/');
        $data['crumbs'] = $crumbs;
        $data['brands'] = $this->getActiveBrands();

        $response = $this->render('search', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Get brands used on active pages.
     *
     * @return Brand[]
     */
    private function getActiveBrands(): array
    {
        $orm = app(OrmService::class);

        $categoryIds = [];
        $categories = $orm->getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            if ($category->isInactive()) {
                continue;
            }
            $categoryIds[] = $category->getId();
        }

        return $orm->getByQuery(
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
    }

    /**
     * Show search results.
     */
    public function results(Request $request): Response
    {
        if ($response = $this->checkSearchable($request)) {
            return $response;
        }

        $searchString = $request->get('q');
        if (!is_string($searchString)) {
            $searchString = '';
        }
        $brandId = $request->query->getInt('maerke');
        $varenr = $request->get('varenr');
        if (!is_string($varenr)) {
            $varenr = '';
        }
        $minpris = $request->query->getInt('minpris', 0);
        $maxpris = $request->query->getInt('maxpris', 0);
        $antiWords = $request->get('sogikke');
        if (!is_string($antiWords)) {
            $antiWords = '';
        }

        $pages = $this->findPages($searchString, $brandId, $varenr, $minpris, $maxpris, $antiWords);
        if (1 === count($pages)) {
            $page = array_shift($pages);

            return redirect($page->getCanonicalLink());
        }

        $brands = $this->findBrands(
            $searchString,
            $antiWords
        );

        $categories = $this->findCategories(
            $searchString,
            $antiWords
        );

        $contentList = array_merge($pages, $brands, $categories);

        $requirement = new VolatilePage('Results', $request->getRequestUri(), $contentList);

        $data = $this->basicPageData();
        $crumbs = $data['crumbs'] ?? null;
        if (!is_array($crumbs)) {
            $crumbs = [];
        }
        $crumbs[] = new VolatilePage(_('Search'), '/search/');
        $crumbs[] = $requirement;
        $data['crumbs'] = $crumbs;

        $data['renderable'] = $requirement;
        $data['search'] = $searchString;

        $response = $this->render('tiles', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Check if we should performe the search or handle it else where.
     */
    private function checkSearchable(Request $request): ?RedirectResponse
    {
        $brandId = $request->get('maerke');
        if (!$request->get('q')
            && !$request->get('varenr')
            && !$request->get('minpris')
            && !$request->get('maxpris')
            && !$request->get('sogikke')
        ) {
            if (ctype_digit($brandId) || is_int($brandId)) {
                $brand = app(OrmService::class)->getOne(Brand::class, (int)$brandId);
                if ($brand && $brand->hasPages()) {
                    return redirect($brand->getCanonicalLink(), Response::HTTP_MOVED_PERMANENTLY);
                }
            }

            return redirect('/search/', Response::HTTP_MOVED_PERMANENTLY);
        }

        return null;
    }

    /**
     * Search for pages and generate a list or redirect if only one was found.
     *
     * @todo search in keywords
     *
     * @return Page[]
     */
    private function findPages(
        string $searchString,
        int $brandId,
        string $varenr = '',
        int $minpris = 0,
        int $maxpris = 0,
        string $antiWords = ''
    ): array {
        $db = app(DbService::class);

        $simpleQuery = '%' . preg_replace('/\s+/u', '%', $searchString) . '%';
        $simpleQuery = $db->quote($simpleQuery);

        //Full search
        $where = '';
        if ($brandId) {
            $where = ' AND `maerke` = ' . $brandId;
        }
        if ($varenr) {
            $where .= ' AND varenr LIKE ' . $db->quote($varenr . '%');
        }
        if ($minpris) {
            $where .= ' AND pris > ' . $minpris;
        }
        if ($maxpris) {
            $where .= ' AND pris < ' . $maxpris;
        }
        if ($antiWords) {
            $simpleAntiQuery = '%' . preg_replace('/\s+/u', '%', $antiWords) . '%';
            $simpleAntiQuery = $db->quote($simpleAntiQuery);
            $where .= ' AND !MATCH (navn, text, beskrivelse) AGAINST(' . $db->quote($antiWords) . ") > 0
            AND `navn` NOT LIKE $simpleAntiQuery
            AND `text` NOT LIKE $simpleAntiQuery
            AND `beskrivelse` NOT LIKE $simpleAntiQuery
            ";
        }

        $db->addLoadedTable('list_rows', 'lists', 'bind');
        $columns = [];
        foreach ($db->fetchArray('SHOW COLUMNS FROM sider') as $column) {
            $columns[] = $column['Field'];
        }

        $against = $db->quote($searchString);

        $pages = app(OrmService::class)->getByQuery(
            Page::class,
            '
            SELECT `' . implode('`, `', $columns) . '`
            FROM (SELECT sider.*, MATCH(navn, text, beskrivelse) AGAINST (' . $against . ') AS score
            FROM sider
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE (
                MATCH (navn, text, beskrivelse) AGAINST(' . $against . ") > 0
                OR `navn` LIKE $simpleQuery
                OR `text` LIKE $simpleQuery
                OR `beskrivelse` LIKE $simpleQuery
            )
            $where
            ORDER BY `score` DESC) x
            UNION
            SELECT sider.* FROM `list_rows`
            JOIN lists ON list_rows.list_id = lists.id
            JOIN sider ON lists.page_id = sider.id
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE list_rows.`cells` LIKE $simpleQuery"
            . $where
        );

        // Remove inactive pages
        foreach ($pages as $key => $page) {
            if ($page->isInactive()) {
                unset($pages[$key]);
            }
        }

        return $pages;
    }

    /**
     * Search for brands.
     *
     * @return Brand[]
     */
    private function findBrands(string $searchString, string $antiWords): array
    {
        if (!$searchString) {
            return [];
        }

        $simpleSearchString = '%' . preg_replace('/\s+/u', '%', $searchString) . '%';
        $simpleAntiWords = $antiWords ? '%' . preg_replace('/\s+/u', '%', $antiWords) . '%' : '';

        $db = app(DbService::class);

        return app(OrmService::class)->getByQuery(
            Brand::class,
            '
            SELECT * FROM `maerke`
            WHERE (
                MATCH (navn) AGAINST(' . $db->quote($searchString) . ') > 0
                OR navn LIKE ' . $db->quote($simpleSearchString) . '
            )
            AND !MATCH (navn) AGAINST(' . $db->quote($antiWords) . ') > 0
            AND navn NOT LIKE ' . $db->quote($simpleAntiWords) . '
            '
        );
    }

    /**
     * Search for categories.
     *
     * @return Category[]
     */
    private function findCategories(string $searchString, string $antiWords): array
    {
        if (!$searchString) {
            return [];
        }

        $simpleSearchString = '%' . preg_replace('/\s+/u', '%', $searchString) . '%';
        $simpleAntiWords = $antiWords ? '%' . preg_replace('/\s+/u', '%', $antiWords) . '%' : '';

        $db = app(DbService::class);

        $categories = app(OrmService::class)->getByQuery(
            Category::class,
            '
            SELECT *, MATCH (navn) AGAINST (' . $db->quote($searchString) . ') AS score
            FROM kat
            WHERE (
                MATCH (navn) AGAINST(' . $db->quote($searchString) . ') > 0
                OR navn LIKE ' . $db->quote($simpleSearchString) . '
            )
            AND !MATCH (navn) AGAINST(' . $db->quote($antiWords) . ') > 0
            AND navn NOT LIKE ' . $db->quote($simpleAntiWords) . "
            AND `vis` != '0'
            ORDER BY score, navn
            "
        );

        $activeCategories = [];
        foreach ($categories as $category) {
            if ($category->isVisible()) {
                $activeCategories[] = $category;
            }
        }

        return $activeCategories;
    }
}
