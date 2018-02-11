<?php namespace Tests\Feature\Http\Controllers\Admin;

use Tests\AdminTestCase;

class BrandControllerTest extends AdminTestCase
{
    public function testIndex(): void
    {
        $this->get('/admin/brands/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">List of brands</div>');
    }

    public function testEditPage(): void
    {
        $this->get('/admin/brands/1/')
            ->assertResponseStatus(200)
            ->assertSee(' value="Test"');
    }

    public function testCreate(): void
    {
        $data = [
            'title'  => 'NoName',
            'link'   => 'https://google.com/',
            'iconId' => 1,
        ];

        $this->json('POST', '/admin/brands/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'maerke',
            [
                'navn'    => $data['title'],
                'link'    => $data['link'],
                'icon_id' => $data['iconId'],
            ]
        );
    }

    public function testCreateNoTitle(): void
    {
        $this->json('POST', '/admin/brands/', [])
            ->assertResponseStatus(422);
    }

    public function testUpdate(): void
    {
        $data = [
            'title'  => 'NoName',
            'link'   => 'https://google.com/',
            'iconId' => 1,
        ];

        $this->json('PUT', '/admin/brands/1/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'maerke',
            [
                'id'      => 1,
                'navn'    => $data['title'],
                'link'    => $data['link'],
                'icon_id' => $data['iconId'],
            ]
        );
    }

    public function testUpdateNoTitle(): void
    {
        $this->json('PUT', '/admin/brands/1/', [])
            ->assertResponseStatus(422);
    }

    public function testUpdate404(): void
    {
        $this->json('PUT', '/admin/brands/404/', ['title'  => 'NoName'])
            ->assertResponseStatus(404);
    }

    public function testDelete(): void
    {
        $this->json('DELETE', '/admin/brands/1/')
            ->assertResponseStatus(200);

        $this->assertDatabaseMissing('maerke', ['id' => 1]);
    }
}
