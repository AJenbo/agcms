<?php namespace AGCMS\Controller;

use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\Page;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\VolatilePage;
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

        return $this->render('search', $data);
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
        $categories = ORM::getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            if ($category->isInactive()) {
                continue;
            }
            $categoryIds[] = $category->getId();
        }

        /** @var Brand[] */
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
        $this->checkSearchable($request);

        $searchString = $request->get('q', '');
        $brandId = $request->query->getInt('maerke');
        $varenr = $request->get('varenr', '');
        $minpris = $request->query->getInt('minpris', 0);
        $maxpris = $request->query->getInt('maxpris', 0);
        $antiWords = $request->get('sogikke', '');

        if ($brandId && !$searchString && !$varenr && !$minpris && !$maxpris && !$antiWords) {
            $brand = ORM::getOne(Brand::class, $brandId);
            if ($brand) {
                return $this->redirect($request, $brand->getCanonicalLink(), Response::HTTP_PERMANENTLY_REDIRECT);
            }
        }

        $pages = $this->findPages($searchString, $brandId, $varenr, $minpris, $maxpris, $antiWords);
        if (1 === count($pages)) {
            $page = array_shift($pages);

            return $this->redirect($request, $page->getCanonicalLink(), Response::HTTP_FOUND);
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

        return $this->render('tiles', $data);
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
                $brand = ORM::getOne(Brand::class, $request->get('maerke'));
                if ($brand) {
                    return $this->redirect($request, $brand->getCanonicalLink(), Response::HTTP_MOVED_PERMANENTLY);
                }
            }

            return $this->redirect($request, '/search/', Response::HTTP_MOVED_PERMANENTLY);
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
        $simpleQuery = db()->esc($simpleQuery);

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
            $simpleAntiQuery = '%' . preg_replace('/\s+/u', '%', $antiWords) . '%';
            $simpleAntiQuery = db()->esc($simpleAntiQuery);
            $where .= ' AND !MATCH (navn, text, beskrivelse) AGAINST(' . db()->eandq($antiWords) . ") > 0
            AND `navn` NOT LIKE '$simpleAntiQuery'
            AND `text` NOT LIKE '$simpleAntiQuery'
            AND `beskrivelse` NOT LIKE '$simpleAntiQuery'
            ";
        }

        Render::addLoadedTable('list_rows');
        Render::addLoadedTable('lists');
        Render::addLoadedTable('bind');
        $columns = [];
        foreach (db()->fetchArray('SHOW COLUMNS FROM sider') as $column) {
            $columns[] = $column['Field'];
        }

        /** @var Page[] */
        $pages = ORM::getByQuery(
            Page::class,
            '
            SELECT `' . implode('`, `', $columns) . '`
            FROM (SELECT sider.*, MATCH(navn, text, beskrivelse) AGAINST (' . db()->eandq($searchString) . ') AS score
            FROM sider
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE (
                MATCH (navn, text, beskrivelse) AGAINST(' . db()->eandq($searchString) . ") > 0
                OR `navn` LIKE '$simpleQuery'
                OR `text` LIKE '$simpleQuery'
                OR `beskrivelse` LIKE '$simpleQuery'
            )
            $where
            ORDER BY `score` DESC) x
            UNION
            SELECT sider.* FROM `list_rows`
            JOIN lists ON list_rows.list_id = lists.id
            JOIN sider ON lists.page_id = sider.id
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE list_rows.`cells` LIKE '$simpleQuery'"
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
        $brands = ORM::getByQuery(
            Brand::class,
            '
            SELECT * FROM `maerke`
            WHERE (
                MATCH (navn) AGAINST(' . db()->eandq($searchString) . ') > 0
                OR navn LIKE ' . db()->eandq($simpleSearchString) . '
            )
            AND !MATCH (navn) AGAINST(' . db()->eandq($antiWords) . ') > 0
            AND navn NOT LIKE ' . db()->eandq($simpleAntiWords) . '
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
        $categories = ORM::getByQuery(
            Category::class,
            '
            SELECT *, MATCH (navn) AGAINST (' . db()->eandq($searchString) . ') AS score
            FROM kat
            WHERE (
                MATCH (navn) AGAINST(' . db()->eandq($searchString) . ') > 0
                OR navn LIKE ' . db()->eandq($simpleSearchString) . '
            )
            AND !MATCH (navn) AGAINST(' . db()->eandq($antiWords) . ') > 0
            AND navn NOT LIKE ' . db()->eandq($simpleAntiWords) . "
            AND `vis` != '0'
            ORDER BY score, navn
            "
        );

        $activeCategories = [];
        foreach ($categories as $category) {
            if ($category->isVisable()) {
                $activeCategories[] = $category;
            }
        }

        return $activeCategories;
    }
}
