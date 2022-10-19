<?php

namespace Tests\Feature\Http\Controllers\Admin;

use Tests\AdminTestCase;

class CustomPageControllerTest extends AdminTestCase
{
    public function testIndex(): void
    {
        $this->get('/admin/custom/3/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit Terms &amp; Conditions</div>');
    }

    public function testIndexRoot(): void
    {
        $this->get('/admin/custom/1/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit Frontpage</div>')
            ->assertSee(' value="Frontpage"')
            ->assertSee('<ul id="subMenus">');
    }

    public function testUpdate(): void
    {
        $data = ['html' => '<p>Terms</p>'];

        $this->json('PUT', '/admin/custom/3/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'special',
            [
                'id'   => 3,
                'text' => $data['html'],
            ]
        );
    }

    public function testUpdateRoot(): void
    {
        $data = [
            'title' => 'The root',
            'html'  => '<p>Terms</p>',
        ];

        $this->json('PUT', '/admin/custom/1/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas('kat', ['id' => 0, 'navn' => $data['title']]);
    }

    public function testUpdate404(): void
    {
        $this->json('PUT', '/admin/custom/404/', [])
            ->assertResponseStatus(404);
    }
}
