<?php namespace Tests\Feature\Http\Controllers\Admin;

use Tests\AdminTestCase;

class CategoryControllerTest extends AdminTestCase
{
    public function testIndex(): void
    {
        $this->get('/admin/categories/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Create category</div>');
    }

    public function testIndexEdit(): void
    {
        $this->get('/admin/categories/1/')
            ->assertResponseStatus(200)
            ->assertSee(' value="Gallery Category"')
            ->assertSee('<option value="1" selected="selected">Gallery</option>');
    }

    public function testCreate(): void
    {
        $data = [
            'title'       => 'New title',
            'parentId'    => -1,
            'render_mode' => 2,
            'email'       => 'mail@example.com',
            'icon_id'     => 1,
        ];

        $this->json('POST', '/admin/categories/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'kat',
            [
                'navn'    => $data['title'],
                'bind'    => $data['parentId'],
                'vis'     => $data['render_mode'],
                'email'   => $data['email'],
                'icon_id' => $data['icon_id'],
            ]
        );
    }

    public function testCreateNoTitle(): void
    {
        $data = [
            'title'       => '',
            'parentId'    => -1,
            'render_mode' => 2,
            'email'       => 'mail@example.com',
            'icon_id'     => 1,
        ];

        $this->json('POST', '/admin/categories/', $data)
            ->assertResponseStatus(422);
    }

    public function testUpdate(): void
    {
        $data = [
            'title'       => 'New title',
            'parentId'    => -1,
            'render_mode' => 2,
            'email'       => 'mail@example.com',
            'icon_id'     => 1,
        ];

        $this->json('PUT', '/admin/categories/1/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'kat',
            [
                'navn'    => $data['title'],
                'bind'    => $data['parentId'],
                'vis'     => $data['render_mode'],
                'email'   => $data['email'],
                'icon_id' => $data['icon_id'],
            ]
        );
    }

    public function testUpdateWithWeight(): void
    {
        $data = [
            'title'            => 'New title',
            'parentId'         => -1,
            'render_mode'      => 2,
            'email'            => 'mail@example.com',
            'icon_id'          => 1,
            'weightedChildren' => true,
            'subMenusOrder'    => '8,7',
        ];

        $this->json('PUT', '/admin/categories/1/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas('kat', ['id' => 8, 'order'=> 0]);
        $this->assertDatabaseHas('kat', ['id' => 7, 'order'=> 1]);
    }

    public function testUpdate404(): void
    {
        $this->json('PUT', '/admin/categories/404/', ['title' => 'NoTitle'])
            ->assertResponseStatus(404);
    }

    public function testDelete(): void
    {
        $this->json('DELETE', '/admin/categories/2/')
            ->assertResponseStatus(200);
    }

    public function testDeleteRoot(): void
    {
        $this->json('DELETE', '/admin/categories/0/')
            ->assertResponseStatus(423);
    }
}
