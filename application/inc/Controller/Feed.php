<?php namespace AGCMS\Controller;

use AGCMS\Config;
use AGCMS\Entity\Category;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\ORM;
use AGCMS\Render;
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
        Render::addLoadedTable('bind');
        Render::addLoadedTable('kat');
        Render::addLoadedTable('sider');
        Render::addLoadedTable('special');
        Render::addLoadedTable('maerke');
        Render::addLoadedTable('krav');
        Render::sendCacheHeader($request);

        $activeCategories = [];
        $activeCategoryIds = [];
        /** @var Category[] */
        $categories = ORM::getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            if ($category->isInactive()) {
                continue;
            }
            $activeCategories[] = $category;
            $activeCategoryIds[] = $category->getId();
        }

        /** @var Page[] */
        $pages = ORM::getByQuery(
            Page::class,
            '
            SELECT sider.* FROM bind
            JOIN sider ON sider.id = bind.side
            WHERE bind.kat IN(' . implode(',', $activeCategoryIds) . ')
            '
        );
        $pages = [];
        $brands = [];
        foreach ($pages as $page) {
            $pages[] = $page;
            $brand = $page->getBrand();
            if ($brand) {
                $brands[$brand->getId()] = $brand;
            }
        }

        $data = [
            'base_url'     => Config::get('base_url'),
            'categories'   => $activeCategories,
            'pages'        => $pages,
            'brands'       => $brands,
            'requirements' => ORM::getByQuery(Requirement::class, 'SELECT * FROM krav'),
        ];
        $content = Render::render('sitemap', $data);

        return new Response($content, 200, ['Content-Type' => 'text/xml;charset=utf-8']);
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
        Render::addLoadedTable('bind');
        Render::addLoadedTable('files');
        Render::addLoadedTable('kat');
        Render::addLoadedTable('maerke');
        Render::addLoadedTable('sider');
        $timestamp = Render::getUpdateTime();
        Render::sendCacheHeader($request, $timestamp);

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
        $pages = ORM::getByQuery(
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
            if ($page->getIcon() && '/images/web/intet-foto.jpg' !== $page->getIcon()->getPath()) {
                $decription .= '<img style="float:left;margin:0 10px 5px 0;" src="'
                    . Config::get('base_url') . encodeUrl($page->getIcon()->getPath()) . '" ><p>';
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
                'title'       => trim($page->getTitle()) ?: Config::get('site_name'),
                'link'        => Config::get('base_url') . encodeUrl($page->getCanonicalLink()),
                'description' => $decription,
                'pubDate'     => gmdate('D, d M Y H:i:s', $page->getTimeStamp()) . ' GMT',
                'categories'  => $categories,
            ];
        }

        $data = [
            'url'           => Config::get('base_url') . '/feed/rss/',
            'title'         => Config::get('site_name'),
            'siteUrl'       => Config::get('base_url') . '/',
            'lastBuildDate' => gmdate('D, d M Y H:i:s', $timestamp) . ' GMT',
            'email'         => first(Config::get('emails'))['address'],
            'siteName'      => Config::get('site_name'),
            'items'         => $items,
        ];
        $content = Render::render('rss', $data);

        return new Response($content, 200, ['Content-Type' => 'application/rss+xml']);
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
        Render::sendCacheHeader($request, Render::getUpdateTime(false));

        $data = [
            'shortName'   => Config::get('site_name'),
            'description' => sprintf(_('Find in %s'), Config::get('site_name')),
            'url'         => Config::get('base_url') . '/search/results/?q={searchTerms}&sogikke=&minpris=&maxpris=&maerke=0',
        ];
        $content = Render::render('opensearch', $data);

        return new Response($content, 200, ['Content-Type' => 'application/opensearchdescription+xml']);
    }
}
