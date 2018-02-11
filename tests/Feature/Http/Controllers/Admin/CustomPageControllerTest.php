<?php namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Tests\TestCase;

class CustomPageControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/custom/3/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit Terms &amp; Conditions</div>');
    }

    public function testIndexRoot(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/custom/1/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit Frontpage</div>')
            ->assertSee(' value="Frontpage"')
            ->assertSee('<ul id="subMenus">');
    }

    public function testUpdate(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $data = ['html'  => '<p>Terms</p>'];

        $this->actingAs($user)->json('PUT', '/admin/custom/3/', $data)
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
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'title' => 'The root',
            'html'  => '<p>Terms</p>',
        ];

        $this->actingAs($user)->json('PUT', '/admin/custom/1/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas('kat', ['id' => 0, 'navn'=>  $data['title']]);
    }

    public function testUpdate404(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('PUT', '/admin/custom/404/', [])
            ->assertResponseStatus(404);
    }
}
