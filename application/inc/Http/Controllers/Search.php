<?php namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\VolatilePage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Search extends Base
{
    /**
     * Show the advanced search form.
     *
     * @return Response
     */
    public function index(): Response
    {
        $data = $this->basicPageData();
        $data['crumbs'][] = new VolatilePage(_('Search'), '/search/');
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
        $categoryIds = [];
        /** @var Category[] */
        $categories = app('orm')->getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            if ($category->isInactive()) {
                continue;
            }
            $categoryIds[] = $category->getId();
        }

        /** @var Brand[] */
        $brands = app('orm')->getByQuery(
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

        return $brands;
    }

    /**
     * Show search results.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function results(Request $request): Response
    {
        if ($response = $this->checkSearchable($request)) {
            return $response;
        }

        $searchString = $request->get('q', '');
        $brandId = $request->query->getInt('maerke');
        $varenr = $request->get('varenr', '');
        $minpris = $request->query->getInt('minpris', 0);
        $maxpris = $request->query->getInt('maxpris', 0);
        $antiWords = $request->get('sogikke', '');

        $pages = $this->findPages($searchString, $brandId, $varenr, $minpris, $maxpris, $antiWords);
        if (1 === count($pages)) {
            $page = array_shift($pages);

            return redirect($page->getCanonicalLink());
        }

        $brands = $this->findBrands(
            $request->get('q', ''),
            $request->get('sogikke', '')
        );

        $categories = $this->findCategories(
            $request->get('q', ''),
            $request->get('sogikke', '')
        );

        $contentList = array_merge($pages, $brands, $categories);

        $requirement = new VolatilePage('Results', $request->getRequestUri(), $contentList);

        $data = $this->basicPageData();
        $data['crumbs'][] = new VolatilePage(_('Search'), '/search/');
        $data['crumbs'][] = $requirement;
        $data['renderable'] = $requirement;
        $data['search'] = $request->get('q', '');

        $response = $this->render('tiles', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Check if we should performe the search or handle it else where.
     *
     * @param Request $request
     *
     * @return ?RedirectResponse
     */
    private function checkSearchable(Request $request): ?RedirectResponse
    {
        if (!$request->get('q')
            && !$request->get('varenr')
            && !$request->get('minpris')
            && !$request->get('maxpris')
            && !$request->get('sogikke')
        ) {
            if ($request->get('maerke')) {
                /** @var ?Brand */
                $brand = app('orm')->getOne(Brand::class, $request->get('maerke'));
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
     * @param string $searchString
     * @param int    $brandId
     * @param string $varenr
     * @param int    $minpris
     * @param int    $maxpris
     * @param string $antiWords
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
        $simpleQuery = '%' . preg_replace('/\s+/u', '%', $searchString) . '%';
        $simpleQuery = app('db')->quote($simpleQuery);

        //Full search
        $where = '';
        if ($brandId) {
            $where = ' AND `maerke` = ' . $brandId;
        }
        if ($varenr) {
            $where .= ' AND varenr LIKE ' . app('db')->quote($varenr . '%');
        }
        if ($minpris) {
            $where .= ' AND pris > ' . $minpris;
        }
        if ($maxpris) {
            $where .= ' AND pris < ' . $maxpris;
        }
        if ($antiWords) {
            $simpleAntiQuery = '%' . preg_replace('/\s+/u', '%', $antiWords) . '%';
            $simpleAntiQuery = app('db')->quote($simpleAntiQuery);
            $where .= ' AND !MATCH (navn, text, beskrivelse) AGAINST(' . app('db')->quote($antiWords) . ") > 0
            AND `navn` NOT LIKE $simpleAntiQuery
            AND `text` NOT LIKE $simpleAntiQuery
            AND `beskrivelse` NOT LIKE $simpleAntiQuery
            ";
        }

        app('db')->addLoadedTable('list_rows', 'lists', 'bind');
        $columns = [];
        foreach (app('db')->fetchArray('SHOW COLUMNS FROM sider') as $column) {
            $columns[] = $column['Field'];
        }

        $against = app('db')->quote($searchString);

        /** @var Page[] */
        $pages = app('orm')->getByQuery(
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
     * @param string $searchString
     * @param string $antiWords
     *
     * @return Brand[]
     */
    private function findBrands(string $searchString, string $antiWords): array
    {
        if (!$searchString) {
            return [];
        }

        $simpleSearchString = $searchString ? '%' . preg_replace('/\s+/u', '%', $searchString) . '%' : '';
        $simpleAntiWords = $antiWords ? '%' . preg_replace('/\s+/u', '%', $antiWords) . '%' : '';

        /** @var Brand[] */
        $brands = app('orm')->getByQuery(
            Brand::class,
            '
            SELECT * FROM `maerke`
            WHERE (
                MATCH (navn) AGAINST(' . app('db')->quote($searchString) . ') > 0
                OR navn LIKE ' . app('db')->quote($simpleSearchString) . '
            )
            AND !MATCH (navn) AGAINST(' . app('db')->quote($antiWords) . ') > 0
            AND navn NOT LIKE ' . app('db')->quote($simpleAntiWords) . '
            '
        );

        return $brands;
    }

    /**
     * Search for categories.
     *
     * @param string $searchString
     * @param string $antiWords
     *
     * @return Category[]
     */
    private function findCategories(string $searchString, string $antiWords): array
    {
        if (!$searchString) {
            return [];
        }

        $simpleSearchString = $searchString ? '%' . preg_replace('/\s+/u', '%', $searchString) . '%' : '';
        $simpleAntiWords = $antiWords ? '%' . preg_replace('/\s+/u', '%', $antiWords) . '%' : '';

        /** @var Category[] */
        $categories = app('orm')->getByQuery(
            Category::class,
            '
            SELECT *, MATCH (navn) AGAINST (' . app('db')->quote($searchString) . ') AS score
            FROM kat
            WHERE (
                MATCH (navn) AGAINST(' . app('db')->quote($searchString) . ') > 0
                OR navn LIKE ' . app('db')->quote($simpleSearchString) . '
            )
            AND !MATCH (navn) AGAINST(' . app('db')->quote($antiWords) . ') > 0
            AND navn NOT LIKE ' . app('db')->quote($simpleAntiWords) . "
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
