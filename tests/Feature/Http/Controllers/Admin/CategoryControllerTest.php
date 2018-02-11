<?php namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/categories/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Create category</div>');
    }

    public function testIndexEdit(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/categories/1/')
            ->assertResponseStatus(200)
            ->assertSee(' value="Gallery Category"')
            ->assertSee('<option value="1" selected="selected">Gallery</option>');
    }

    public function testCreate(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'title'       => 'New title',
            'parentId'    => -1,
            'render_mode' => 2,
            'email'       => 'mail@example.com',
            'icon_id'     => 1,
        ];

        $this->actingAs($user)->json('POST', '/admin/categories/', $data)
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
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'title'       => '',
            'parentId'    => -1,
            'render_mode' => 2,
            'email'       => 'mail@example.com',
            'icon_id'     => 1,
        ];

        $this->actingAs($user)->json('POST', '/admin/categories/', $data)
            ->assertResponseStatus(422);
    }

    public function testUpdate(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'title'       => 'New title',
            'parentId'    => -1,
            'render_mode' => 2,
            'email'       => 'mail@example.com',
            'icon_id'     => 1,
        ];

        $this->actingAs($user)->json('PUT', '/admin/categories/1/', $data)
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
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'title'            => 'New title',
            'parentId'         => -1,
            'render_mode'      => 2,
            'email'            => 'mail@example.com',
            'icon_id'          => 1,
            'weightedChildren' => true,
            'subMenusOrder'    => '8,7',
        ];

        $this->actingAs($user)->json('PUT', '/admin/categories/1/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas('kat', ['id' => 8, 'order'=> 0]);
        $this->assertDatabaseHas('kat', ['id' => 7, 'order'=> 1]);
    }

    public function testUpdate404(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('PUT', '/admin/categories/404/', ['title' => 'NoTitle'])
            ->assertResponseStatus(404);
    }

    public function testDelete(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('DELETE', '/admin/categories/2/')
            ->assertResponseStatus(200);
    }

    public function testDeleteRoot(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('DELETE', '/admin/categories/0/')
            ->assertResponseStatus(423);
    }
}
