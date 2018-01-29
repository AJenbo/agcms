<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Controller\Ajax;
use AGCMS\Tests\TestCase;

class AjaxTest extends TestCase
{
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
