<?php

namespace Tests\Feature\Http\Middleware;

use Tests\TestCase;

class Utf8UrlTest extends TestCase
{
    public function testOpenSearch(): void
    {
        $this->get('/%F8')
            ->assertResponseStatus(301)
            ->assertRedirect('/%C3%B8');
    }
}
