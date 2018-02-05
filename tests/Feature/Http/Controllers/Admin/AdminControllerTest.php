<?php namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $user = app('orm')->getOne(User::class, 1);
        $this->actingAs($user)->get('/admin/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Administration</title>');
    }
}
