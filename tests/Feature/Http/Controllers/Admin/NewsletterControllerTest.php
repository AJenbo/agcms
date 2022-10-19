<?php

namespace Tests\Feature\Http\Controllers\Admin;

use Tests\AdminTestCase;

class NewsletterControllerTest extends AdminTestCase
{
    public function testIndex(): void
    {
        $this->get('/admin/newsletters/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Newsletters</div>');
    }

    public function testEditNewsletter(): void
    {
        $this->get('/admin/newsletters/2/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit newsletter</div>');
    }

    public function testEditNewsletterView(): void
    {
        $this->get('/admin/newsletters/1/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">View newsletter</div>');
    }

    public function testEditNewsletterNew(): void
    {
        $this->get('/admin/newsletters/new/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit newsletter</div>');
    }

    public function testEditNewsletter404(): void
    {
        $this->get('/admin/newsletters/404/')
            ->assertResponseStatus(404);
    }

    public function testCreate(): void
    {
        $data = [
            'from'      => 'mail@gmail.com',
            'subject'   => 'Test',
            'html'      => '<p>Next body</p>',
            'interests' => ['cats', 'dogs'],
        ];

        $this->json('POST', '/admin/newsletters/', $data)
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
        $data = [
            'from'      => 'mail@gmail.com',
            'subject'   => 'Test',
            'html'      => '<p>Next body</p>',
            'interests' => ['cats', 'dogs'],
            'send'      => false,
        ];

        $this->json('PUT', '/admin/newsletters/2/', $data)
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'newsmails',
            [
                'id'        => 2,
                'from'      => $data['from'],
                'subject'   => $data['subject'],
                'text'      => $data['html'],
                'interests' => 'cats<dogs',
                'sendt'     => 0,
            ]
        );
    }

    public function testUpdateSent(): void
    {
        $this->json('PUT', '/admin/newsletters/1/', [])
            ->assertResponseStatus(423);
    }

    public function testUpdate404(): void
    {
        $this->json('PUT', '/admin/newsletters/404/', [])
            ->assertResponseStatus(404);
    }

    public function testUpdateSend(): void
    {
        static::markTestSkipped('Still not able to dalay sending newsletters');

        /** @phpstan-ignore-next-line */
        $data = [
            'from'      => 'mail@gmail.com',
            'subject'   => 'Test',
            'html'      => '<p>Next body</p>',
            'interests' => ['cats', 'dogs'],
            'send'      => true,
        ];

        $this->json('PUT', '/admin/newsletters/2/', [])
            ->assertResponseStatus(200);

        $this->assertDatabaseHas(
            'newsmails',
            [
                'id'        => 2,
                'from'      => $data['from'],
                'subject'   => $data['subject'],
                'text'      => $data['html'],
                'interests' => 'cats<dogs',
                'sendt'     => 1,
            ]
        );
    }

    public function testCountRecipients(): void
    {
        $this->json('GET', '/admin/addressbook/count/?interests[]=cats')
            ->assertResponseStatus(200)
            ->assertJson(['count' => 4]);
    }

    public function testCountRecipientsMultiple(): void
    {
        $this->json('GET', '/admin/addressbook/count/?interests[]=cats&interests[]=dogs')
            ->assertResponseStatus(200)
            ->assertJson(['count' => 5]);
    }
}
