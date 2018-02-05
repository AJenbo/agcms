<?php namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class ShoppingTest extends TestCase
{
    public function testBasket(): void
    {
        $basket = ['items' => [['type' => 'page', 'id' => 6, 'quantity' => 1]]];
        $this->json('GET', '/order/?cart=' . rawurlencode(json_encode($basket)))
            ->assertResponseStatus(200)
            ->assertSee('Product 1 Green - sku3')
            ->assertSee('var values = [20];')
            ->assertNotSee('* The price cannot be determined automatically');
    }

    public function testBasketLineItem(): void
    {
        $basket = ['items' => [['type' => 'line', 'id' => 2, 'quantity' => 1]]];
        $this->json('GET', '/order/?cart=' . rawurlencode(json_encode($basket)))
            ->assertResponseStatus(200)
            ->assertSee('Blue')
            ->assertSee('var values = [17];')
            ->assertNotSee('* The price cannot be determined automatically');
    }

    public function testBasketLineItem404(): void
    {
        $basket = ['items' => [['type' => 'line', 'id' => 404, 'quantity' => 1]]];
        $this->json('GET', '/order/?cart=' . rawurlencode(json_encode($basket)))
            ->assertResponseStatus(200)
            ->assertSee('<title>Shopping list</title>')
            ->assertSee('<td>Expired</td>');
    }

    public function testBasketUnknownPrice(): void
    {
        $basket = ['items' => [['type' => 'page', 'id' => 2, 'quantity' => 1]]];
        $this->json('GET', '/order/?cart=' . rawurlencode(json_encode($basket)))
            ->assertResponseStatus(200)
            ->assertSee('<title>Shopping list</title>')
            ->assertSee('Page 1')
            ->assertSee('* The price cannot be determined automatically');
    }

    public function testBasketExpired(): void
    {
        $basket = ['items' => [['type' => 'page', 'id' => 5, 'quantity' => 1]]];
        $this->json('GET', '/order/?cart=' . rawurlencode(json_encode($basket)))
            ->assertResponseStatus(200)
            ->assertSee('<title>Shopping list</title>')
            ->assertSee('<td>Expired</td>');
    }

    public function testBasket404(): void
    {
        $basket = ['items' => [['type' => 'page', 'id' => 404, 'quantity' => 1]]];
        $this->json('GET', '/order/?cart=' . rawurlencode(json_encode($basket)))
            ->assertResponseStatus(200)
            ->assertSee('<title>Shopping list</title>')
            ->assertSee('<td>Expired</td>');
    }

    public function testBasketInvalid(): void
    {
        $this->json('GET', '/order/?cart=')
            ->assertResponseStatus(422);
    }

    public function testAddress(): void
    {
        $this->json('GET', '/order/address/?cart={"items":[]}')
            ->assertResponseStatus(200)
            ->assertSee('<option value="DK" selected="selected">');
    }

    public function testAddressInvalid(): void
    {
        $this->json('GET', '/order/address/?cart=Wrong')
            ->assertResponseStatus(422);
    }

    public function testSend(): void
    {
        $cart = [
           'items'              => [
              [
                 'type'     => 'page',
                 'id'       => 2,
                 'quantity' => 1,
              ],
              [
                 'type'     => 'page',
                 'id'       => 6,
                 'quantity' => 1,
              ],
           ],
           'name'               => 'Name',
           'attn'               => 'Attn',
           'address'            => 'Address 1',
           'postbox'            => 'Postboks',
           'postcode'           => '4000',
           'city'               => 'Roskilde',
           'country'            => 'DK',
           'email'              => 'test@excample.com',
           'phone1'             => '99999999',
           'phone2'             => '88888888',
           'hasShippingAddress' => false,
           'shippingPhone'      => '',
           'shippingName'       => '',
           'shippingAttn'       => '',
           'shippingAddress'    => '',
           'shippingAddress2'   => '',
           'shippingPostbox'    => '',
           'shippingPostcode'   => '',
           'shippingCity'       => '',
           'shippingCountry'    => 'DK',
           'note'               => 'Note',
           'payMethod'          => 'creditcard',
           'deleveryMethod'     => 'postal',
           'newsletter'         => false,
        ];
        $redirectCart = $cart;
        $redirectCart['items'] = [];

        $this->post('/order/send/', ['cart' => json_encode($cart)])
            ->assertResponseStatus(303)
            ->assertRedirect('/order/receipt/?cart=' . rawurlencode(json_encode($redirectCart)));

        $this->assertDatabaseHas(
            'fakturas',
            [
                'status'         => 'new',
                'quantities'     => '1<1',
                'products'       => 'Page 1<Product 1 Green - sku3',
                'values'         => '0<20',
                'discount'       => '0',
                'fragt'          => '0',
                'amount'         => '20',
                'momssats'       => 0.25,
                'premoms'        => 1,
                'transferred'    => 0,
                'cardtype'       => 'Unknown',
                'iref'           => '',
                'eref'           => '',
                'navn'           => $cart['name'],
                'att'            => $cart['attn'],
                'adresse'        => $cart['address'],
                'postbox'        => $cart['postbox'],
                'postnr'         => $cart['postcode'],
                'by'             => $cart['city'],
                'land'           => $cart['country'],
                'email'          => $cart['email'],
                'sendt'          => 0,
                'tlf1'           => $cart['phone1'],
                'tlf2'           => $cart['phone2'],
                'altpost'        => 0,
                'posttlf'        => $cart['shippingPhone'],
                'postname'       => $cart['shippingName'],
                'postatt'        => $cart['shippingAttn'],
                'postaddress'    => $cart['shippingAddress'],
                'postaddress2'   => $cart['shippingAddress2'],
                'postpostbox'    => $cart['shippingPostbox'],
                'postpostalcode' => $cart['shippingPostcode'],
                'postcity'       => $cart['shippingCity'],
                'postcountry'    => $cart['shippingCountry'],
                'clerk'          => '',
                'department'     => '',
                'note'           => 'I would like to pay via credit card.
Please send the goods by mail.
Note',
                'enote'          => '',
            ]
        );
        $this->assertDatabaseHas(
            'emails',
            [
                'id'      => '1',
                'subject' => 'Online order #4',
                'from'    => $cart['email'] . '<' . $cart['name'] . '>',
                'to'      => 'mail@gmail.com<My store>',
            ]
        );

        $this->assertDatabaseMissing('email', ['email' => $cart['email']]);
    }

    public function testSendShipping(): void
    {
        $cart = [
           'items'              => [
              [
                 'type'     => 'page',
                 'id'       => 6,
                 'quantity' => 1,
              ],
           ],
           'name'               => 'Name',
           'attn'               => 'Attn',
           'address'            => 'Address 1',
           'postbox'            => 'Postboks',
           'postcode'           => '4000',
           'city'               => 'Roskilde',
           'country'            => 'DK',
           'email'              => 'test@excample.com',
           'phone1'             => '99999999',
           'phone2'             => '88888888',
           'hasShippingAddress' => true,
           'shippingPhone'      => '77777777',
           'shippingName'       => 'Shipping street',
           'shippingAttn'       => 'Shipping Attn',
           'shippingAddress'    => 'Shipping Address 1',
           'shippingAddress2'   => 'Shipping Address 2',
           'shippingPostbox'    => '8000',
           'shippingPostcode'   => 'Shipping Postcode',
           'shippingCity'       => 'Ålborg',
           'shippingCountry'    => 'DK',
           'note'               => 'Note',
           'payMethod'          => 'creditcard',
           'deleveryMethod'     => 'postal',
           'newsletter'         => false,
        ];
        $redirectCart = $cart;
        $redirectCart['items'] = [];

        $this->post('/order/send/', ['cart' => json_encode($cart)])
            ->assertResponseStatus(303)
            ->assertRedirect('/order/receipt/?cart=' . rawurlencode(json_encode($redirectCart)));

        $this->assertDatabaseHas(
            'fakturas',
            [
                'status'         => 'new',
                'quantities'     => '1',
                'products'       => 'Product 1 Green - sku3',
                'values'         => '20',
                'discount'       => '0',
                'fragt'          => '0',
                'amount'         => '20',
                'momssats'       => 0.25,
                'premoms'        => 1,
                'transferred'    => 0,
                'cardtype'       => 'Unknown',
                'iref'           => '',
                'eref'           => '',
                'navn'           => $cart['name'],
                'att'            => $cart['attn'],
                'adresse'        => $cart['address'],
                'postbox'        => $cart['postbox'],
                'postnr'         => $cart['postcode'],
                'by'             => $cart['city'],
                'land'           => $cart['country'],
                'email'          => $cart['email'],
                'sendt'          => 0,
                'tlf1'           => $cart['phone1'],
                'tlf2'           => $cart['phone2'],
                'altpost'        => 1,
                'posttlf'        => $cart['shippingPhone'],
                'postname'       => $cart['shippingName'],
                'postatt'        => $cart['shippingAttn'],
                'postaddress'    => $cart['shippingAddress'],
                'postaddress2'   => $cart['shippingAddress2'],
                'postpostbox'    => $cart['shippingPostbox'],
                'postpostalcode' => $cart['shippingPostcode'],
                'postcity'       => $cart['shippingCity'],
                'postcountry'    => $cart['shippingCountry'],
                'clerk'          => '',
                'department'     => '',
                'note'           => 'I would like to pay via credit card.
Please send the goods by mail.
Note',
                'enote'          => '',
            ]
        );
        $this->assertDatabaseHas(
            'emails',
            [
                'id'      => '1',
                'subject' => 'Online order #4',
                'from'    => $cart['email'] . '<' . $cart['name'] . '>',
                'to'      => 'mail@gmail.com<My store>',
            ]
        );

        $this->assertDatabaseMissing('email', ['email' => $cart['email']]);
    }

    public function testSendNewsletter(): void
    {
        $cart = [
           'items'     => [
              [
                 'type'     => 'page',
                 'id'       => 2,
                 'quantity' => 1,
              ],
           ],
           'name'       => 'Name',
           'address'    => 'Address 1',
           'postcode'   => '4000',
           'city'       => 'Roskilde',
           'country'    => 'DK',
           'email'      => 'test@excample.com',
           'newsletter' => true,
        ];
        $redirectCart = $cart;
        $redirectCart['items'] = [];

        $this->post('/order/send/', ['cart' => json_encode($cart)]);

        $this->assertDatabaseHas('email', ['email' => $cart['email']]);
        $this->assertDatabaseHas(
            'emails',
            [
                'id'      => '1',
                'subject' => 'Online order #4',
                'from'    => $cart['email'] . '<' . $cart['name'] . '>',
                'to'      => 'mail@gmail.com<My store>',
            ]
        );
    }

    public function testSendNewsletterDuplicate(): void
    {
        $cart = [
           'items'      => [
              [
                 'type'     => 'page',
                 'id'       => 2,
                 'quantity' => 1,
              ],
           ],
           'name'       => 'Name',
           'address'    => 'Address 1',
           'postcode'   => '4000',
           'city'       => 'Roskilde',
           'country'    => 'DK',
           'email'      => 'john-email@excample.com',
           'newsletter' => true,
        ];
        $redirectCart = $cart;
        $redirectCart['items'] = [];

        $this->post('/order/send/', ['cart' => json_encode($cart)]);

        $this->assertDatabaseHas('email', ['id' => 1, 'email' => $cart['email']]);
        $this->assertDatabaseHas(
            'emails',
            [
                'id'      => '1',
                'subject' => 'Online order #4',
                'from'    => $cart['email'] . '<' . $cart['name'] . '>',
                'to'      => 'mail@gmail.com<My store>',
            ]
        );
    }

    public function testSendInvalidData(): void
    {
        $this->post('/order/send/', ['cart' => 'wrong'])
            ->assertResponseStatus(422);
    }

    public function testSendInvalid(): void
    {
        $cart = [
           'items'    => [],
           'name'     => '',
           'address'  => 'Address 1',
           'postcode' => '4000',
           'city'     => 'Roskilde',
           'country'  => 'DK',
           'email'    => 'test@excample.com',
        ];
        $payload = json_encode($cart);

        $this->post('/order/send/', ['cart' => $payload])
            ->assertResponseStatus(303)
            ->assertRedirect('/order/address/?cart=' . rawurlencode($payload));
    }

    public function testSendEmpty(): void
    {
        $cart = [
           'items'    => [],
           'name'     => 'Name',
           'address'  => 'Address 1',
           'postcode' => '4000',
           'city'     => 'Roskilde',
           'country'  => 'DK',
           'email'    => 'test@excample.com',
        ];
        $payload = json_encode($cart);

        $this->post('/order/send/', ['cart' => $payload])
            ->assertResponseStatus(303)
            ->assertRedirect('/order/?cart=' . rawurlencode($payload));
    }

    public function testReceat(): void
    {
        $rawCart = '{"items":[]}';
        $this->json('GET', '/order/receipt/?cart=' . $rawCart)
            ->assertResponseStatus(200)
            ->assertSee('/order/?cart=' . rawurlencode($rawCart))
            ->assertSee('/order/address/?cart=' . rawurlencode($rawCart))
            ->assertSee('/order/receipt/?cart=' . rawurlencode($rawCart));
    }
}
