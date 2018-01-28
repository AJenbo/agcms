<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Tests\TestCase;

class Utf8UrlTest extends TestCase
{
    public function testOpenSearch(): void
    {
        $this->get('/%F8')
            ->assertResponseStatus(301)
            ->assertRedirect('/%C3%B8');
    }
}
