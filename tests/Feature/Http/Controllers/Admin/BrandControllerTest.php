<?php namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Tests\TestCase;

class BrandControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/brands/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">List of brands</div>');
    }

    public function testEditPage(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/brands/1/')
            ->assertResponseStatus(200)
            ->assertSee(' value="Test"');
    }

    public function testCreate(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'title'  => 'NoName',
            'link'   => 'https://google.com/',
            'iconId' => 1,
        ];

        $this->actingAs($user)->json('POST', '/admin/brands/', $data)
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
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('POST', '/admin/brands/', [])
            ->assertResponseStatus(422);
    }

    public function testUpdate(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'title'  => 'NoName',
            'link'   => 'https://google.com/',
            'iconId' => 1,
        ];

        $this->actingAs($user)->json('PUT', '/admin/brands/1/', $data)
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
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('PUT', '/admin/brands/1/', [])
            ->assertResponseStatus(422);
    }

    public function testUpdate404(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('PUT', '/admin/brands/404/', ['title'  => 'NoName'])
            ->assertResponseStatus(404);
    }

    public function testDelete(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('DELETE', '/admin/brands/1/')
            ->assertResponseStatus(200);

        $this->assertDatabaseMissing('maerke', ['id' => 1]);
    }
}
