<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Tests\TestCase;

class FeedTest extends TestCase
{
    public function testSiteMap(): void
    {
        $this->get('/sitemap.xml')
            ->assertResponseStatus(200)
            ->assertSee('<?xml version="1.0" encoding="utf-8"?>')
            ->assertSee('<loc>https://localhost/search/</loc>')
            ->assertSee('<loc>https://localhost/kat1-Gallery-Category/</loc>')
            ->assertSee('<loc>https://localhost/kat1-Gallery-Category/side2-Page-1.html</loc>')
            ->assertSee('<loc>https://localhost/kat6-Indexed-Category/side4-Category-Index-Page.html</loc>')
            ->assertSee('<loc>https://localhost/krav/1/Test.html</loc>')
            ->assertSee('<loc>https://localhost/mærke1-Test/</loc>')
            ->assertNotSee('<loc>https://localhost/kat3-Empty-Category/</loc>')
            ->assertNotSee('<loc>https://localhost/kat4-Inactive-Category/</loc>')
            ->assertNotSee('<loc>https://localhost/kat5-Hidden-Category/</loc>')
            ->assertNotSee('<loc>https://localhost/kat6-Indexed-Category/</loc>')
            ->assertNotSee('<loc>https://localhost/mærke2-Empty-Brand/</loc>');
    }

    public function testSiteMapCache(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(time() + 3600);

        $this->get('/sitemap.xml', ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(304);
    }

    public function testRss(): void
    {
        $this->get('/feed/rss/')
            ->assertResponseStatus(200)
            ->assertSee('<?xml version="1.0" encoding="utf-8"?>')
            ->assertSee('<title>My store</title>')
            ->assertSee('<guid>https://localhost/side1-Root-Page.html</guid>')
            ->assertSee(' src=&quot;https://localhost/images/test.jpg&quot;');
    }

    public function testRssCache(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(time() + 3600);

        $this->get('/feed/rss/', ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(304);
    }

    public function testRssRevisit(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(1514955600); // 2018-01-03 06:00:00

        $this->get('/feed/rss/', ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(200)
            ->assertSee('<guid>https://localhost/kat6-Indexed-Category/side4-Category-Index-Page.html</guid>')
            ->assertNotSee('<guid>https://localhost/side1-Root-Page.html</guid>');
    }

    public function testOpenSearch(): void
    {
        $this->get('/opensearch.xml')
            ->assertResponseStatus(200)
            ->assertSee('<?xml version="1.0" encoding="utf-8"?>')
            ->assertSee('<Url type="text/html" template="https://localhost/search/results/?q={searchTerms}&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=0" />');
    }

    public function testOpenSearchCache(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(time() + 3600);

        $this->get('/opensearch.xml', ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(304);
    }
}
