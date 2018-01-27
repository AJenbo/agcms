<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Tests\TestCase;

class SiteTest extends TestCase
{
    public function testFrontPage(): void
    {
        $this->call('GET', '/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Frontpage</title>');
    }

    public function testCategory(): void
    {
        $this->call('GET', '/kat1-Gallery-Category/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Gallery Category</title>')
            ->assertSee('<table cellspacing="0" id="liste">');
    }

    public function testCategoryBadSlug(): void
    {
        $this->call('GET', '/kat1-Wrong/')
            ->assertResponseStatus(301)
            ->assertRedirect('/kat1-Gallery-Category/');
    }

    public function testCategoryWrongId(): void
    {
        $this->call('GET', '/kat404-Gallery-Category/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Gallery%20Category&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testCategoryList(): void
    {
        $this->call('GET', '/kat2-List-Category/')
            ->assertResponseStatus(200)
            ->assertSee('<title>List Category</title>')
            ->assertSee('<table class="tabel">');
    }

    public function testCategoryEmpty(): void
    {
        $this->call('GET', '/kat3-Empty-Category/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Empty%20Category&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testCategoryInactive(): void
    {
        $this->call('GET', '/kat4-Inactive-Category/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Inactive%20Category&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testCategoryHidden(): void
    {
        $this->call('GET', '/kat5-Hidden-Category/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Hidden%20Category&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testCategoryWithIndexPage(): void
    {
        $this->call('GET', '/kat6-Indexed-Category/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Category Index Page</title>');
    }

    public function testPage(): void
    {
        $this->call('GET', '/kat1-Gallery-Category/side2-Page-1.html')
            ->assertResponseStatus(200)
            ->assertSee('<title>Page 1</title>');
    }

    public function testPageBadId(): void
    {
        $this->call('GET', '/kat1-Gallery-Category/side404-Page-1.html')
            ->assertResponseStatus(301)
            ->assertRedirect('/kat1-Gallery-Category/');
    }

    public function testPageBadSlug(): void
    {
        $this->call('GET', '/kat1-Gallery-Category/side2-Wrong.html')
            ->assertResponseStatus(301)
            ->assertRedirect('/kat1-Gallery-Category/side2-Page-1.html');
    }

    public function testRootPage(): void
    {
        $this->call('GET', '/side1-Root-Page.html')
            ->assertResponseStatus(200)
            ->assertSee('<title>Root Page</title>');
    }

    public function testRootPageBadSlug(): void
    {
        $this->call('GET', '/side1-Wrong.html')
            ->assertResponseStatus(301)
            ->assertRedirect('/side1-Root-Page.html');
    }

    public function testRootPageBadId(): void
    {
        $this->call('GET', '/side404-Root-Page.html')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Root%20Page&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testBrand(): void
    {
        $this->call('GET', '/mærke1-Test/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Test</title>');
    }

    public function testBrandBadId(): void
    {
        $this->call('GET', '/mærke404-Test/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Test&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testBrandBadSlug(): void
    {
        $this->call('GET', '/mærke1-Wrong/')
            ->assertResponseStatus(301)
            ->assertRedirect('/m%C3%A6rke1-Test/');
    }

    public function testBrandEmpty(): void
    {
        $this->call('GET', '/mærke2-Empty-Brand/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Empty%20Brand&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testRequirement(): void
    {
        $this->call('GET', '/krav/1/Test.html')
            ->assertResponseStatus(200)
            ->assertSee('<title>Test</title>');
    }

    public function testRequirementBadId(): void
    {
        $this->call('GET', '/krav/404/Test.html')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=krav%20404%20Test&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testRequirementBadSlug(): void
    {
        $this->call('GET', '/krav/1/Wrong.html')
            ->assertResponseStatus(301)
            ->assertRedirect('/krav/1/Test.html');
    }
}
