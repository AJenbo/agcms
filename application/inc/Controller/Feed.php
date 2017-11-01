<?php namespace AGCMS\Controller;

use AGCMS\Config;
use AGCMS\Entity\Page;
use AGCMS\Entity\Category;
use AGCMS\Entity\Requirement;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Feed extends Base
{
    /**
     * Generate a Google Site Map
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
        Render::sendCacheHeader();

        $urls = [
            [
                'loc' => Config::get('base_url') . '/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=',
                'lastmod' => Render::getUpdateTime(false),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
        ];

        $activeCategoryIds = [0];
        $categories = ORM::getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            assert($category instanceof Category);
            if ($category->isInactive()) {
                continue;
            }
            $activeCategoryIds[] = $category->getId();

            $urls[] = [
                'loc' => Config::get('base_url') . $category->getCanonicalLink(),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ];
        }
        unset($categories, $category);

        $brandIds = [];
        $pages = ORM::getByQuery(
            Page::class,
            '
            SELECT sider.* FROM bind
            JOIN sider ON sider.id = bind.side
            WHERE bind.kat IN(' . implode(',', $activeCategoryIds) . ')
            '
        );
        foreach ($pages as $page) {
            assert($page instanceof Page);
            $urls[] = [
                'loc' => Config::get('base_url') . $page->getCanonicalLink(),
                'lastmod' => $page->getTimeStamp(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        if ($brandIds) {
            $brands = ORM::getByQuery(
                Brand::class,
                '
                SELECT * FROM maerke
                WHERE id IN(' . implode(',', array_keys($brandIds)) . ')
                '
            );
            foreach ($brands as $brand) {
                assert($brand instanceof Brand);
                $urls[] = [
                    'loc' => Config::get('base_url') . $brand->getCanonicalLink(),
                    'changefreq' => 'weekly',
                    'priority' => '0.4',
                ];
            }
        }

        $requirements = ORM::getByQuery(Requirement::class, 'SELECT * FROM krav');
        foreach ($requirements as $requirement) {
            assert($requirement instanceof Requirement);
            $urls[] = [
                'loc' => Config::get('base_url') . $requirement->getCanonicalLink(),
                'changefreq' => 'monthly',
                'priority' => '0.2',
            ];
        }

        $content = Render::render('sitemap', ['urls' => $urls]);

        return new Response($content, 200, ['Content-Type' => 'text/xml;charset=utf-8']);
    }

    /**
     * Rss feed of most recently updated articles
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
        Render::sendCacheHeader($timestamp);

        $time = 0;
        if (request()->headers->has('If-Modified-Since')) {
            $time = strtotime(stripslashes(request()->headers->get('If-Modified-Since')));
        }

        $where = '';
        $limit = ' LIMIT 20';
        if ($time) {
            $where = " WHERE `dato` > '" . date('Y-m-d h:i:s', $time) . "'";
            $limit = '';
        }

        $items = [];
        $pages = ORM::getByQuery(
            Page::class,
            'SELECT * FROM sider'
            . $where
            . ' ORDER BY dato DESC'
            . $limit
        );
        foreach ($pages as $page) {
            assert($page instanceof Page);
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
                'title' => trim($page->getTitle()) ?: Config::get('site_name'),
                'link' => Config::get('base_url') . encodeUrl($page->getCanonicalLink()),
                'description' => $decription,
                'pubDate' => gmdate('D, d M Y H:i:s', $page->getTimeStamp()) . ' GMT',
                'categories' => $categories,
            ];
        }

        $data = [
            'url' => Config::get('base_url') . '/feed/rss/',
            'title' => Config::get('site_name'),
            'siteUrl' => Config::get('base_url') . '/',
            'lastBuildDate' => gmdate('D, d M Y H:i:s', $timestamp) . ' GMT',
            'email' => first(Config::get('emails'))['address'],
            'siteName' => Config::get('site_name'),
            'items' => $items,
        ];
        $content = Render::render('rss', $data);

        return new Response($content, 200, ['Content-Type' => 'application/rss+xml']);
    }

    /**
     * Generate an OpenSearch configuration
     *
     * @param Request $request
     *
     * @return Response
     */
    public function openSearch(Request $request): Response
    {
        Render::sendCacheHeader(Render::getUpdateTime(false));

        $data = [
            'shortName' => Config::get('site_name'),
            'description' => sprintf(_('Find in %s'), Config::get('site_name')),
            'url' => Config::get('base_url') . '/?q={searchTerms}',
        ];
        $content = Render::render('opensearch', $data);

        return new Response($content, 200, ['Content-Type' => 'application/opensearchdescription+xml']);
    }
}
