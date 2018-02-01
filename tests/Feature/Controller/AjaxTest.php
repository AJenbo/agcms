<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Controller\Ajax;
use AGCMS\Tests\TestCase;

class AjaxTest extends TestCase
{
    public function testTable(): void
    {
        $response = $this->json('GET', '/ajax/category/1/table/1/0');

        $response->assertResponseStatus(200)
            ->assertJson(['id' => 'table1'])
            ->assertJsonStructure(['id', 'html']);

        $data = $response->json();
        $this->assertContains('<caption>Variants</caption>', $data['html']);
        $this->assertRegExp('/side7.*side6.*side8/su', $data['html']);
    }

    public function testTableOrder(): void
    {
        $response = $this->json('GET', '/ajax/category/1/table/1/1');

        $response->assertResponseStatus(200)
            ->assertJson(['id' => 'table1'])
            ->assertJsonStructure(['id', 'html']);

        $data = $response->json();
        $this->assertRegExp('/side8.*side7.*side6/su', $data['html']);
    }

    public function testTableCustomOrder(): void
    {
        $response = $this->json('GET', '/ajax/category/1/table/1/2');

        $response->assertResponseStatus(200)
            ->assertJson(['id' => 'table1'])
            ->assertJsonStructure(['id', 'html']);

        $data = $response->json();
        $this->assertRegExp('/side8.*side6.*side7/su', $data['html']);
    }

    public function testTableCache(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(time() + 3600);

        $this->json('GET', '/ajax/category/1/table/1/0', [], ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(304);
    }

    public function testTable404(): void
    {
        $this->json('GET', '/ajax/category/1/table/404/0')
            ->assertResponseStatus(404);
    }

    public function testCategory(): void
    {
        $response = $this->json('GET', '/ajax/category/2/navn');

        $response->assertResponseStatus(200)
            ->assertJson(['id' => 'kat2'])
            ->assertJsonStructure(['id', 'html']);

        $data = $response->json();
        $this->assertRegExp('/side3.*side7.*side6.*side8/su', $data['html']);
    }

    public function testCategoryOldPrice(): void
    {
        $response = $this->json('GET', '/ajax/category/2/for');

        $response->assertResponseStatus(200)
            ->assertJson(['id' => 'kat2'])
            ->assertJsonStructure(['id', 'html']);

        $data = $response->json();
        $this->assertRegExp('/side6.*side3.*side7.*side8/su', $data['html']);
    }

    public function testCategoryPrice(): void
    {
        $response = $this->json('GET', '/ajax/category/2/pris');

        $response->assertResponseStatus(200)
            ->assertJson(['id' => 'kat2'])
            ->assertJsonStructure(['id', 'html']);

        $data = $response->json();
        $this->assertRegExp('/side8.*side7.*side3.*side6/su', $data['html']);
    }

    public function testCategorySku(): void
    {
        $response = $this->json('GET', '/ajax/category/2/varenr');

        $response->assertResponseStatus(200)
            ->assertJson(['id' => 'kat2'])
            ->assertJsonStructure(['id', 'html']);

        $data = $response->json();
        $this->assertRegExp('/side8.*side3.*side6.*side7/su', $data['html']);
    }

    public function testCategoryCache(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(time() + 3600);

        $this->json('GET', '/ajax/category/2/navn', [], ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(304);
    }

    public function testAddressInvoice1(): void
    {
        $this->json('GET', '/ajax/address/88888888')
            ->assertResponseStatus(200)
            ->assertJson([
                'name'     => 'John Doe',
                'attn'     => 'Jane Doe',
                'address1' => '50 Oakland Ave',
                'address2' => '',
                'zipcode'  => '32104',
                'postbox'  => 'P.O. box #578',
                'email'    => 'john@example.com',
            ]);
    }

    public function testAddressInvoice2(): void
    {
        $this->json('GET', '/ajax/address/88888889')
            ->assertResponseStatus(200)
            ->assertJson([
                'name'     => 'John Doe',
                'attn'     => 'Jane Doe',
                'address1' => '50 Oakland Ave',
                'address2' => '',
                'zipcode'  => '32104',
                'postbox'  => 'P.O. box #578',
                'email'    => 'john@example.com',
            ]);
    }

    public function testAddressInvoice3(): void
    {
        $this->json('GET', '/ajax/address/88888890')
            ->assertResponseStatus(200)
            ->assertJson([
                'name'     => 'Jane Doe',
                'attn'     => 'John Doe',
                'address1' => '20 Shipping rd.',
                'address2' => 'Collage Green',
                'zipcode'  => '902010',
                'postbox'  => 'P.O. box #382',
                'email'    => 'john@example.com',
            ]);
    }

    public function testAddressEmail1(): void
    {
        $this->json('GET', '/ajax/address/88888891')
            ->assertResponseStatus(200)
            ->assertJson([
                'name'     => 'John Email',
                'attn'     => '',
                'address1' => '48 Email street',
                'address2' => '',
                'zipcode'  => '31047',
                'postbox'  => '',
                'email'    => 'john-email@excample.com',
            ]);
    }

    public function testAddressEmail2(): void
    {
        $this->json('GET', '/ajax/address/88888892')
            ->assertResponseStatus(200)
            ->assertJson([
                'name'     => 'John Email',
                'attn'     => '',
                'address1' => '48 Email street',
                'address2' => '',
                'zipcode'  => '31047',
                'postbox'  => '',
                'email'    => 'john-email@excample.com',
            ]);
    }

    public function testAddressPost(): void
    {
        $this->json('GET', '/ajax/address/88888893')
            ->assertResponseStatus(200)
            ->assertJson([
                'name'     => 'John Post',
                'attn'     => '',
                'address1' => '48 Post street',
                'address2' => '',
                'zipcode'  => '80447',
                'postbox'  => '',
                'email'    => '',
            ]);
    }

    public function testAddressUnknown(): void
    {
        $this->json('GET', '/ajax/address/+404 404 404', [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertResponseStatus(404)
            ->assertJson(['error' => ['message' => Ajax::MESSAGE_ADDRESS_NOT_FOUND]]);
    }

    public function testAddressCache(): void
    {
        // Set the call one hour in to the feature to make sure the data is older
        $ifModifiedSince = $this->timeToHeader(time() + 3600);

        $this->json('GET', '/ajax/address/88888888', [], ['If-Modified-Since' => $ifModifiedSince])
            ->assertResponseStatus(304);
    }
}
