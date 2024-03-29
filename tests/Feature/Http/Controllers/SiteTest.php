<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class SiteTest extends TestCase
{
    public function testFrontPage(): void
    {
        $this->get('/')
            ->assertResponseStatus(200)
            ->assertSee('Wellcome')
            ->assertSee('<title>Frontpage</title>');
    }

    public function testFrontPageCache(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(time() + 3600);

        $this->get('/', ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(304);
    }

    public function testCategory(): void
    {
        $this->get('/kat1-Gallery-Category/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Gallery Category</title>')
            ->assertSee('<table cellspacing="0" id="liste">');
    }

    public function testCategoryHead(): void
    {
        $this->head('/kat1-Gallery-Category/')
            ->assertResponseStatus(200)
            ->assertNotSee('<title>Gallery Category</title>');
    }

    public function testCategoryBadSlug(): void
    {
        $this->get('/kat1-Wrong/')
            ->assertResponseStatus(301)
            ->assertRedirect('/kat1-Gallery-Category/');
    }

    public function testCategoryWrongId(): void
    {
        $this->get('/kat404-Gallery-Category/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Gallery%20Category&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testCategoryList(): void
    {
        $this->get('/kat2-List-Category/')
            ->assertResponseStatus(200)
            ->assertSee('<title>List Category</title>')
            ->assertSee('<table class="tabel">');
    }

    public function testCategoryEmpty(): void
    {
        $this->get('/kat3-Empty-Category/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Empty%20Category&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testCategoryInactive(): void
    {
        $this->get('/kat4-Inactive-Category/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Inactive%20Category&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testCategoryHidden(): void
    {
        $this->get('/kat5-Hidden-Category/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Hidden%20Category&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testCategoryWithIndexPage(): void
    {
        $this->get('/kat6-Indexed-Category/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Category Index Page</title>');
    }

    public function testPage(): void
    {
        $this->get('/kat1-Gallery-Category/side2-Page-1.html')
            ->assertResponseStatus(200)
            ->assertSee('<title>Page 1</title>');
    }

    public function testProductVariants(): void
    {
        $response = $this->get('/kat2-List-Category/side3-Product-1.html');

        $response->assertResponseStatus(200)
            ->assertSee('<title>Product 1</title>')
            ->assertSee('<caption>Variants</caption>')
            ->assertSee('17.00')
            ->assertSee('20.00')
            ->assertSee('16.00')
            ->assertSee('<a href="" onclick="shoppingCart.addItem(\'line\',1);return false"><img src="/theme/default/images/cart_add.png" title="Add to shopping cart" alt="+" /></a>')
            ->assertSee('<a href="" onclick="shoppingCart.addItem(\'line\',2);return false"><img src="/theme/default/images/cart_add.png" title="Add to shopping cart" alt="+" /></a>')
            ->assertSee('<a href="" onclick="shoppingCart.addItem(\'line\',3);return false"><img src="/theme/default/images/cart_add.png" title="Add to shopping cart" alt="+" /></a>');

        $data = $response->getContent();
        static::assertMatchesRegularExpression('/<thead>.*<tr>.*<td><a href="" onclick="return getTable\(2, 1, 0\)">.*Title.*?<\/a><\/td>.*<\/tr>.*<\/thead>/su', $data);
        static::assertMatchesRegularExpression('/<thead>.*<tr>.*<td><a href="" onclick="return getTable\(2, 1, 1\)">.*Price.*?<\/a><\/td>.*<\/tr>.*<\/thead>/su', $data);
        static::assertMatchesRegularExpression('/<thead>.*<tr>.*<td><a href="" onclick="return getTable\(2, 1, 2\)">.*Size.*?<\/a><\/td>.*<\/tr>.*<\/thead>/su', $data);
        static::assertMatchesRegularExpression('/<tbody>.*<tr>.*<td><a href="\/kat2-List-Category\/side7-Product-1-Blue.html">\\s*Product 1 Blue.*<\/a><\/td>.*<\/tr>.*<\/tbody>/su', $data);
        static::assertMatchesRegularExpression('/<tbody>.*<tr>.*<td><a href="\/kat2-List-Category\/side6-Product-1-Green.html">\\s*Product 1 Green.*<\/a><\/td>.*<\/tr>.*<\/tbody>/su', $data);
        static::assertMatchesRegularExpression('/<tbody>.*<tr>.*<td><a href="\/kat2-List-Category\/side8-Product-1-Red.html">\\s*Product 1 Red.*<\/a><\/td>.*<\/tr>.*<\/tbody>/su', $data);
    }

    public function testPagePlusEncoding(): void
    {
        $this->get('/kat5-Hidden-Category/side9-Power+.html')
            ->assertResponseStatus(200)
            ->assertSee('<title>Power+</title>');
    }

    public function testPageBadId(): void
    {
        $this->get('/kat1-Gallery-Category/side404-Page-1.html')
            ->assertResponseStatus(301)
            ->assertRedirect('/kat1-Gallery-Category/');
    }

    public function testPageBadSlug(): void
    {
        $this->get('/kat1-Gallery-Category/side2-Wrong.html')
            ->assertResponseStatus(301)
            ->assertRedirect('/kat1-Gallery-Category/side2-Page-1.html');
    }

    public function testRootPage(): void
    {
        $this->get('/side1-Root-Page.html')
            ->assertResponseStatus(200)
            ->assertSee('<title>Root Page</title>');
    }

    public function testRootPageBadSlug(): void
    {
        $this->get('/side1-Wrong.html')
            ->assertResponseStatus(301)
            ->assertRedirect('/side1-Root-Page.html');
    }

    public function testRootPageBadId(): void
    {
        $this->get('/side404-Root-Page.html')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Root%20Page&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testBrand(): void
    {
        $this->get('/mærke1-Test/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Test</title>');
    }

    public function testBrandBadId(): void
    {
        $this->get('/mærke404-Test/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Test&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testBrandBadSlug(): void
    {
        $this->get('/mærke1-Wrong/')
            ->assertResponseStatus(301)
            ->assertRedirect('/m%C3%A6rke1-Test/');
    }

    public function testBrandEmpty(): void
    {
        $this->get('/mærke2-Empty-Brand/')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=Empty%20Brand&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testRequirement(): void
    {
        $this->get('/krav/1/Test.html')
            ->assertResponseStatus(200)
            ->assertSee('<title>Test</title>');
    }

    public function testRequirementBadId(): void
    {
        $this->get('/krav/404/Test.html')
            ->assertResponseStatus(303)
            ->assertRedirect('/search/results/?q=krav%20404%20Test&sogikke=&minpris=&maxpris=&maerke=0');
    }

    public function testRequirementBadSlug(): void
    {
        $this->get('/krav/1/Wrong.html')
            ->assertResponseStatus(301)
            ->assertRedirect('/krav/1/Test.html');
    }
}
