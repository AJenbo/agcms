<?php namespace Tests\Feature\Http\Controllers\Admin;

use Tests\AdminTestCase;

class AdminControllerTest extends AdminTestCase
{
    public function testIndex(): void
    {
        $this->get('/admin/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Administration</title>');
    }
}
