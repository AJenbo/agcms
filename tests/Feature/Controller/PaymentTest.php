<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Tests\TestCase;

class PaymentTest extends TestCase
{
    public function testIndex(): void
    {
        $this->json('GET', '/betaling/?id=1&checkid=a4238')
            ->assertResponseStatus(200)
            ->assertSee('<input id="id" name="id" value="1" />')
            ->assertSee('<input id="checkid" name="checkid" value="a4238" />');
    }

    public function testBasket(): void
    {
        $this->json('GET', '/betaling/1/a4238/')
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
        $this->json('GET', '/betaling/1/wrong/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/?id=1&checkid=wrong');
    }

    public function testBasketFinalized(): void
    {
        $this->json('GET', '/betaling/3/bc87e/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/3/bc87e/status/');
    }

    public function testAddress(): void
    {
        $this->json('GET', '/betaling/1/a4238/address/')
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
        $this->json('GET', '/betaling/2/e728d/address/')
            ->assertResponseStatus(200)
            ->assertSee('<option value="DK" selected="selected">')
            ->assertSee(' id="hasShippingAddress" type="checkbox" />');
    }

    public function testAddressInvalid(): void
    {
        $this->json('GET', '/betaling/1/wrong/address/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/?id=1&checkid=wrong');
    }

    public function testTerms(): void
    {
        $id = 1;
        $baseUrl = '/betaling/' . $id . '/a4238/';
        $this->json('GET', $baseUrl . 'terms/')
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
        $this->json('GET', '/betaling/1/wrong/terms/')
            ->assertResponseStatus(303)
            ->assertRedirect('/betaling/?id=1&checkid=wrong');
    }
}
