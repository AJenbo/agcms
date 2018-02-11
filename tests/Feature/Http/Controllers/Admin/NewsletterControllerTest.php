<?php namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Tests\TestCase;

class NewsletterControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/newsletters/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Newsletters</div>');
    }

    public function testEditNewsletter(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/newsletters/2/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit newsletter</div>');
    }

    public function testEditNewsletterView(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/newsletters/1/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">View newsletter</div>');
    }

    public function testEditNewsletterNew(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/newsletters/new/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit newsletter</div>');
    }

    public function testEditNewsletter404(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->get('/admin/newsletters/404/')
            ->assertResponseStatus(404);
    }

    public function testCreate(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'from'      => 'mail@gmail.com',
            'subject'   => 'Test',
            'html'      => '<p>Next body</p>',
            'interests' => ['cats', 'dogs'],
        ];

        $this->actingAs($user)->json('POST', '/admin/newsletters/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'newsmails',
            [
                'from'      => $data['from'],
                'subject'   => $data['subject'],
                'text'      => $data['html'],
                'interests' => 'cats<dogs',
            ]
        );
    }

    public function testUpdate(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $data = [
            'from'      => 'mail@gmail.com',
            'subject'   => 'Test',
            'html'      => '<p>Next body</p>',
            'interests' => ['cats', 'dogs'],
            'send'      => false,
        ];

        $this->actingAs($user)->json('PUT', '/admin/newsletters/2/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'newsmails',
            [
                'id'         => 2,
                'from'       => $data['from'],
                'subject'    => $data['subject'],
                'text'       => $data['html'],
                'interests'  => 'cats<dogs',
                'sendt'      => 0,
            ]
        );
    }

    public function testUpdateSent(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('PUT', '/admin/newsletters/1/', [])
            ->assertResponseStatus(423);
    }

    public function testUpdate404(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('PUT', '/admin/newsletters/404/', [])
            ->assertResponseStatus(404);
    }

    public function testCountRecipients(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('GET', '/admin/addressbook/count/?interests[]=cats')
            ->assertResponseStatus(200)
            ->assertJson(['count' => 4]);
    }

    public function testCountRecipientsMultiple(): void
    {
        $user = app('orm')->getOne(User::class, 1);

        $this->actingAs($user)->json('GET', '/admin/addressbook/count/?interests[]=cats&interests[]=dogs')
            ->assertResponseStatus(200)
            ->assertJson(['count' => 5]);
    }
}
