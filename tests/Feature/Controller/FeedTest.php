<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Tests\TestCase;

class FeedTest extends TestCase
{
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
            ->assertSee('<guid>https://localhost/side1-Root-Page.html</guid>');
    }

    public function testRssCache(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(time() + 3600);

        $this->get('/feed/rss/', ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(304);
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
