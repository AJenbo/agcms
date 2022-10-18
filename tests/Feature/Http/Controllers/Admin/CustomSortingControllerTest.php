<?php

namespace Tests\Feature\Http\Controllers\Admin;

use Tests\AdminTestCase;

class CustomSortingControllerTest extends AdminTestCase
{
    public function testIndex(): void
    {
        $this->get('/admin/sortings/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">List sorting</div>');
    }

    public function testListsortEditNew(): void
    {
        $this->get('/admin/sortings/new/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit </div>');
    }

    public function testListsortEdit(): void
    {
        $this->get('/admin/sortings/1/')
            ->assertResponseStatus(200)
            ->assertSee('<div id="headline">Edit Size</div>');
    }

    public function testListsort404(): void
    {
        $this->get('/admin/sortings/404/')
            ->assertResponseStatus(404);
    }

    public function testCreate(): void
    {
        $data = [
            'title' => 'New title',
            'items' => ['Duck', 'Rabit'],
        ];

        $this->json('POST', '/admin/sortings/', $data)
            ->assertResponseStatus(200)
            ->assertJson(['id' => 2]);

        $this->assertDatabaseHas(
            'tablesort',
            [
                'id'   => 2,
                'navn' => $data['title'],
                'text' => 'Duck<Rabit',
            ]
        );
    }

    public function testCreateNoTitle(): void
    {
        $this->json('POST', '/admin/sortings/', ['title' => ''])
            ->assertResponseStatus(422);
    }

    public function testUpdate(): void
    {
        $data = [
            'title' => 'New title',
            'items' => ['Duck', 'Rabit'],
        ];

        $this->json('PUT', '/admin/sortings/1/', $data)
            ->assertResponseStatus(200)
            ->assertJson([]);

        $this->assertDatabaseHas(
            'tablesort',
            [
                'id'   => 1,
                'navn' => $data['title'],
                'text' => 'Duck<Rabit',
            ]
        );
    }

    public function testUpdateNoTitle(): void
    {
        $this->json('PUT', '/admin/sortings/1/', ['title' => ''])
            ->assertResponseStatus(422);
    }

    public function testUpdate404(): void
    {
        $this->json('PUT', '/admin/sortings/404/', ['title' => 'New title'])
            ->assertResponseStatus(404);
    }
}
