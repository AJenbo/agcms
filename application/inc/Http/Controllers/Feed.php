<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Requirement;
use App\Services\DbService;
use App\Services\OrmService;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Feed extends Base
{
    /**
     * Generate a Google Site Map.
     */
    public function siteMap(Request $request): Response
    {
        app(DbService::class)->addLoadedTable('bind', 'kat', 'sider', 'special', 'maerke', 'krav');
        $response = new Response('', Response::HTTP_OK, ['Content-Type' => 'text/xml;charset=utf-8']);
        $response = $this->cachedResponse($response);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $orm = app(OrmService::class);

        $activeCategories = [];
        $activeCategoryIds = [];
        $categories = $orm->getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            if ($category->isInactive()) {
                continue;
            }
            $pageCount = count($category->getPages());
            if ($category->isVisible() && 1 !== $pageCount) {
                $activeCategories[] = $category;
            }
            $activeCategoryIds[] = $category->getId();
        }

        $pages = $orm->getByQuery(
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
            'requirements' => $orm->getByQuery(Requirement::class, 'SELECT * FROM krav'),
        ];

        return $this->render('sitemap', $data, $response);
    }

    /**
     * Rss feed of most recently updated articles.
     *
     * If a If-Modefied-Since is detected the feed will extend to that date, else it will limit to 20 items
     */
    public function rss(Request $request): Response
    {
        $db = app(DbService::class);

        $db->addLoadedTable('bind', 'files', 'kat', 'maerke', 'sider');
        $response = new Response('', Response::HTTP_OK, ['Content-Type' => 'application/rss+xml']);
        $response = $this->cachedResponse($response);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $time = false;
        if ($request->headers->has('If-Modified-Since')) {
            /** @var string */
            $ifModifiedSince = $request->headers->get('If-Modified-Since');
            $time = strtotime($ifModifiedSince);
        }

        $where = '';
        $limit = ' LIMIT 20';
        if ($time) {
            $where = " WHERE `dato` > '" . date('Y-m-d h:i:s', $time) . "'";
            $limit = '';
        }

        $items = [];
        $pages = app(OrmService::class)->getByQuery(
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
            $icon = $page->getIcon();
            if ($icon) {
                $imgUrl = (string) new Uri(config('base_url') . $icon->getPath());
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
                'link'        => (string) new Uri(config('base_url') . $page->getCanonicalLink()),
                'description' => $decription,
                'pubDate'     => gmdate('D, d M Y H:i:s', $page->getTimeStamp()) . ' GMT',
                'categories'  => $categories,
            ];
        }

        $data = [
            'url'           => config('base_url') . '/feed/rss/',
            'title'         => config('site_name'),
            'siteUrl'       => config('base_url') . '/',
            'lastBuildDate' => gmdate('D, d M Y H:i:s', $db->dataAge() ?: time()) . ' GMT',
            'email'         => first(config('emails'))['address'],
            'siteName'      => config('site_name'),
            'items'         => $items,
        ];

        return $this->render('rss', $data, $response);
    }

    /**
     * Generate an OpenSearch configuration.
     */
    public function openSearch(Request $request): Response
    {
        $response = new Response('', Response::HTTP_OK, ['Content-Type' => 'application/opensearchdescription+xml']);
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
