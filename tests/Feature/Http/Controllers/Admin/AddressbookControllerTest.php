<?php

namespace Tests\Feature\Http\Controllers\Admin;

use Tests\AdminTestCase;

class AddressbookControllerTest extends AdminTestCase
{
    public function testIndex(): void
    {
        $this->get('/admin/addressbook/list/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Address Book</div>');
    }

    public function testIndexInvalidOrder(): void
    {
        $this->get('/admin/addressbook/list/?order=wrong')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Address Book</div>');
    }

    public function testEditContact(): void
    {
        $this->get('/admin/addressbook/2/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit contact person</div>');
    }

    public function testEditContactNew(): void
    {
        $this->get('/admin/addressbook/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit contact person</div>');
    }

    public function testCreate(): void
    {
        $data = [
            'name'       => 'Joe',
            'email'      => 'test@excample.com',
            'address'    => 'Some Address 1',
            'country'    => 'Ukrain',
            'postcode'   => '4879',
            'city'       => 'City Name',
            'phone1'     => '33333333',
            'phone2'     => '22222222',
            'newsletter' => true,
            'interests'  => ['cats', 'mise'],
        ];

        $this->json('POST', '/admin/addressbook/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'email',
            [
                'navn'       => $data['name'],
                'email'      => $data['email'],
                'adresse'    => $data['address'],
                'land'       => $data['country'],
                'post'       => $data['postcode'],
                'by'         => $data['city'],
                'tlf1'       => $data['phone1'],
                'tlf2'       => $data['phone2'],
                'kartotek'   => (int) $data['newsletter'],
                'interests'  => 'cats<mise',
            ]
        );
    }

    public function testUpdate(): void
    {
        $data = [
            'name'       => 'Joe',
            'email'      => 'test@excample.com',
            'address'    => 'Some Address 1',
            'country'    => 'Ukrain',
            'postcode'   => '4879',
            'city'       => 'City Name',
            'phone1'     => '33333333',
            'phone2'     => '22222222',
            'newsletter' => true,
            'interests'  => ['cats', 'mise'],
        ];

        $this->json('PUT', '/admin/addressbook/1/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'email',
            [
                'id'         => 1,
                'navn'       => $data['name'],
                'email'      => $data['email'],
                'adresse'    => $data['address'],
                'land'       => $data['country'],
                'post'       => $data['postcode'],
                'by'         => $data['city'],
                'tlf1'       => $data['phone1'],
                'tlf2'       => $data['phone2'],
                'kartotek'   => (int) $data['newsletter'],
                'interests'  => 'cats<mise',
            ]
        );
    }

    public function testUpdate404(): void
    {
        $this->json('PUT', '/admin/addressbook/404/', [])
            ->assertResponseStatus(404);
    }

    public function testDelete(): void
    {
        $this->json('DELETE', '/admin/addressbook/1/', [])
            ->assertResponseStatus(200);

        $this->assertDatabaseMissing('email', ['id' => 1]);
    }

    public function testIsValidEmail(): void
    {
        $this->json('GET', '/admin/addressbook/validEmail/?email=test%40excample.com')
            ->assertResponseStatus(200)
            ->assertJson(['isValid' => true]);
    }
}
