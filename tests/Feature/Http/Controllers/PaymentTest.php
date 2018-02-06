<?php namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class PaymentTest extends TestCase
{
    const PAYLOAD = [
       'name'               => 'Name',
       'attn'               => 'Attn',
       'address'            => 'Address 1',
       'postbox'            => 'Postboks',
       'postcode'           => '4000',
       'city'               => 'Roskilde',
       'country'            => 'DK',
       'email'              => 'test@excample.com',
       'phone1'             => '77777777',
       'phone2'             => '66666666',
       'hasShippingAddress' => '0',
       'shippingPhone'      => '',
       'shippingName'       => 'John',
       'shippingAttn'       => 'Jane',
       'shippingAddress'    => '',
       'shippingAddress2'   => '',
       'shippingPostbox'    => '',
       'shippingPostcode'   => '',
       'shippingCity'       => '',
       'shippingCountry'    => 'DK',
       'note'               => 'Note',
       'payMethod'          => 'creditcard',
       'deleveryMethod'     => 'postal',
       'newsletter'         => '0',
    ];

    public function testIndex(): void
    {
        $this->get('/betaling/?id=1&checkid=a4238')
            ->assertResponseStatus(200)
            ->assertSee('<input id="id" name="id" value="1" />')
            ->assertSee('<input id="checkid" name="checkid" value="a4238" />');
    }

    public function testBasket(): void
    {
        $this->get('/betaling/1/a4238/')
            ->assertResponseStatus(200)
            ->assertSee('100')
            ->assertSee('80')
            ->assertSee('59')
            ->assertSee('25%')
            ->assertSee('20')
            ->assertSee('159');

        $this->assertDatabaseHas('fakturas', ['id' => 1, 'status' => 'locked']);
    }

    public function testBasketInvalid(): void
    {
        $this->get('/betaling/1/wrong/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/?id=1&checkid=wrong');
    }

    public function testBasketFinalized(): void
    {
        $this->get('/betaling/3/bc87e/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/3/bc87e/status/');
    }

    public function testAddress(): void
    {
        $this->get('/betaling/1/a4238/address/')
            ->assertResponseStatus(200)
            ->assertSee(' id="phone1" style="width:157px" value="88888888" />')
            ->assertSee(' id="phone2" style="width:157px" value="88888889" />')
            ->assertSee(' id="name" style="width:157px" value="John Doe" />')
            ->assertSee(' id="attn" style="width:157px" value="Jane Doe" />')
            ->assertSee(' id="address" style="width:157px" value="50 Oakland Ave" />')
            ->assertSee(' id="postbox" style="width:157px" value="P.O. box #578" />')
            ->assertSee(' id="postcode" style="width:35px" value="32104" ')
            ->assertSee(' id="city" style="width:90px" value="A City, Florida" />')
            ->assertSee(' id="email" style="width:157px" value="john@example.com" />')
            ->assertSee(' id="hasShippingAddress" type="checkbox" checked="checked" />')
            ->assertSee(' id="shippingName" style="width:157px" value="Jane Doe" />')
            ->assertSee(' id="shippingAttn" style="width:157px" value="John D. Doe" />')
            ->assertSee(' id="shippingAddress" style="width:157px" value="20 Shipping rd." />')
            ->assertSee(' id="shippingAddress2" style="width:157px" value="Collage Green" />')
            ->assertSee(' id="shippingPostbox" style="width:157px" value="P.O. box #382" />')
            ->assertSee(' id="shippingPostcode" style="width:35px" value="90210" ')
            ->assertSee(' id="shippingCity" style="width:90px" value="Beverly hills" />')
            ->assertSee(' value="DK" selected="selected">');
    }

    public function testAddressOtherShipping(): void
    {
        $this->get('/betaling/2/e728d/address/')
            ->assertResponseStatus(200)
            ->assertSee('<option value="DK" selected="selected">')
            ->assertSee(' id="hasShippingAddress" type="checkbox" />');
    }

    public function testAddressInvalid(): void
    {
        $this->get('/betaling/1/wrong/address/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/?id=1&checkid=wrong');
    }

    public function testAddressSave(): void
    {
        $payload = self::PAYLOAD;

        $this->post('/betaling/1/a4238/address/', $payload)
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/1/a4238/terms/');

        $this->assertDatabaseHas(
            'fakturas',
            [
                'id' => '1',
                'navn' => 'Name',
                'att' => 'Attn',
                'tlf1' => '77777777',
                'tlf2' => '66666666',
                'postname' => 'John',
                'postatt' => 'Jane',
            ]
        );

        $this->assertDatabaseMissing('email', ['email' => $payload['email']]);
    }

    public function testAddressSaveNewsletter(): void
    {
        $payload = ['newsletter' => '1'] + self::PAYLOAD;

        $this->post('/betaling/1/a4238/address/', $payload)
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/1/a4238/terms/');

        $this->assertDatabaseHas(
            'fakturas',
            [
                'id' => '1',
                'navn' => 'Name',
                'att' => 'Attn',
                'tlf1' => '77777777',
                'tlf2' => '66666666',
                'postname' => 'John',
                'postatt' => 'Jane',
            ]
        );

        $this->assertDatabaseHas('email', ['email' => $payload['email']]);
    }

    public function testAddressSaveIdenticalInfo(): void
    {
        $payload = [
           'name'         => 'Name',
           'attn'         => 'Name',
           'phone1'       => '77777777',
           'phone2'       => '77777777',
           'shippingName' => 'John',
           'shippingAttn' => 'John',
        ] + self::PAYLOAD;

        $this->post('/betaling/1/a4238/address/', $payload)
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/1/a4238/terms/');

        $this->assertDatabaseHas(
            'fakturas',
            [
                'id' => '1',
                'navn' => 'Name',
                'att' => '',
                'tlf1' => '',
                'tlf2' => '77777777',
                'postname' => 'John',
                'postatt' => '',
            ]
        );
    }

    public function testAddressSaveInvalid(): void
    {
        $payload = ['name' => ''] + self::PAYLOAD;

        $url = '/betaling/1/a4238/address/';
        $this->post($url, $payload)
            ->assertResponseStatus(303)
            ->assertRedirect($url);
    }

    public function testAddressSaveInvalidNewsletter(): void
    {
        $payload = ['name' => '', 'newsletter' => '1'] + self::PAYLOAD;

        $url = '/betaling/1/a4238/address/';
        $this->post($url, $payload)
            ->assertResponseStatus(303)
            ->assertRedirect($url. '?newsletter=1');
    }

    public function testAddressSaveWrong(): void
    {
        $this->post('/betaling/1/wrong/address/', [])
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/?id=1&checkid=wrong');
    }

    public function testTerms(): void
    {
        $id = 1;
        $baseUrl = '/betaling/' . $id . '/a4238/';
        $this->get($baseUrl . 'terms/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Trade Conditions</title>')
            ->assertSee('="https://ssl.ditonlinebetalingssystem.dk/integration/ewindow/Default.aspx" method="post">')
            ->assertSee(' name="group" value="' . config('pbsfix') . '"')
            ->assertSee(' name="merchantnumber" value="' . config('pbsid') . '"')
            ->assertSee(' name="orderid" value="' . config('pbsfix') . $id . '"')
            ->assertSee(' name="currency" value="208"')
            ->assertSee(' name="amount" value="15900"')
            ->assertSee(' name="ownreceipt" value="1"')
            ->assertSee(' name="accepturl" value="https://localhost' . $baseUrl . 'status/"')
            ->assertSee(' name="cancelurl" value="https://localhost' . $baseUrl . 'terms/"')
            ->assertSee(' name="callbackurl" value="https://localhost' . $baseUrl . 'callback/"')
            ->assertSee(' name="windowstate" value="3"')
            ->assertSee(' name="windowid" value="' . config('pbswindow') . '"');
    }

    public function testTermsInvalid(): void
    {
        $this->get('/betaling/1/wrong/terms/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/?id=1&checkid=wrong');
    }

    public function testStatusNew(): void
    {
        $this->get('/betaling/1/a4238/status/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/1/a4238/');
    }

    public function testStatusWrong(): void
    {
        $this->get('/betaling/1/wrong/status/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/?id=1&checkid=wrong');
    }

    public function testStatusCancled(): void
    {
        $this->get('/betaling/3/bc87e/status/')
            ->assertResponseStatus(200);
    }
}
