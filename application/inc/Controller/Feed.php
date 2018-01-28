<?php namespace AGCMS\Controller;

use AGCMS\Entity\Category;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Feed extends Base
{
    /**
     * Generate a Google Site Map.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function siteMap(Request $request): Response
    {
        app('db')->addLoadedTable('bind', 'kat', 'sider', 'special', 'maerke', 'krav');
        $response = new Response('', 200, ['Content-Type' => 'text/xml;charset=utf-8']);
        $response = $this->cachedResponse($response);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $activeCategories = [];
        $activeCategoryIds = [];
        /** @var Category[] */
        $categories = app('orm')->getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            if ($category->isInactive()) {
                continue;
            }
            $activeCategories[] = $category;
            $activeCategoryIds[] = $category->getId();
        }

        /** @var Page[] */
        $pages = app('orm')->getByQuery(
            Page::class,
            '
            SELECT sider.* FROM bind
            JOIN sider ON sider.id = bind.side
            WHERE bind.kat IN(' . implode(',', $activeCategoryIds) . ')
            '
        );
        $brands = [];
        foreach ($pages as $page) {
            if ($brand = $page->getBrand()) {
                $brands[$brand->getId()] = $brand;
            }
        }
        $data = [
            'base_url'     => config('base_url'),
            'categories'   => $activeCategories,
            'pages'        => $pages,
            'brands'       => $brands,
            'requirements' => app('orm')->getByQuery(Requirement::class, 'SELECT * FROM krav'),
        ];

        return $this->render('sitemap', $data, $response);
    }

    /**
     * Rss feed of most recently updated articles.
     *
     * If a If-Modefied-Since is detected the feed will extend to that date, else it will limit to 20 items
     *
     * @param Request $request
     *
     * @return Response
     */
    public function rss(Request $request): Response
    {
        app('db')->addLoadedTable('bind', 'files', 'kat', 'maerke', 'sider');
        $response = new Response('', 200, ['Content-Type' => 'application/rss+xml']);
        $response = $this->cachedResponse($response);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $time = false;
        if ($request->headers->has('If-Modified-Since')) {
            $time = strtotime($request->headers->get('If-Modified-Since'));
        }

        $where = '';
        $limit = ' LIMIT 20';
        if ($time) {
            $where = " WHERE `dato` > '" . date('Y-m-d h:i:s', $time) . "'";
            $limit = '';
        }

        $items = [];
        /** @var Page[] */
        $pages = app('orm')->getByQuery(
            Page::class,
            'SELECT * FROM sider'
            . $where
            . ' ORDER BY dato DESC'
            . $limit
        );
        foreach ($pages as $page) {
            if ($page->isInactive()) {
                continue;
            }

            $decription = '';
            if ($page->getIcon()) {
                $imgUrl = config('base_url') . encodeUrl($page->getIcon()->getPath());
                $decription .= '<img style="float:left;margin:0 10px 5px 0" src="'
                    . htmlspecialchars($imgUrl, ENT_COMPAT | ENT_XHTML) . '" ><p>';
            }
            $decription .= $page->getExcerpt() . '</p>';

            $categories = [];
            foreach ($page->getCategories() as $category) {
                do {
                    $categories[] = $category->getTitle();
                } while ($category = $category->getParent());
            }
            $brand = $page->getBrand();
            if ($brand) {
                $categories[] = $brand->getTitle();
            }
            $categories = array_map('trim', $categories);
            $categories = array_filter($categories);
            $categories = array_unique($categories);

            $items[] = [
                'title'       => trim($page->getTitle()) ?: config('site_name'),
                'link'        => config('base_url') . encodeUrl($page->getCanonicalLink()),
                'description' => $decription,
                'pubDate'     => gmdate('D, d M Y H:i:s', $page->getTimeStamp()) . ' GMT',
                'categories'  => $categories,
            ];
        }

        $data = [
            'url'           => config('base_url') . '/feed/rss/',
            'title'         => config('site_name'),
            'siteUrl'       => config('base_url') . '/',
            'lastBuildDate' => gmdate('D, d M Y H:i:s', app('db')->dataAge()) . ' GMT',
            'email'         => first(config('emails'))['address'],
            'siteName'      => config('site_name'),
            'items'         => $items,
        ];

        return $this->render('rss', $data, $response);
    }

    /**
     * Generate an OpenSearch configuration.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function openSearch(Request $request): Response
    {
        $response = new Response('', 200, ['Content-Type' => 'application/opensearchdescription+xml']);
        $response = $this->cachedResponse($response);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $url = config('base_url') . '/search/results/?q={searchTerms}&sogikke=&minpris=&maxpris=&maerke=0';
        $data = [
            'shortName'   => config('site_name'),
            'description' => sprintf(_('Find in %s'), config('site_name')),
            'url'         => $url,
        ];

        return $this->render('opensearch', $data, $response);
    }
}
