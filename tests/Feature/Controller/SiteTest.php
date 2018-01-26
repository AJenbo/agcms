<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Tests\TestCase;

class SiteTest extends TestCase
{
    public function testFrontPage(): void
    {
        $this->call('GET', '/')
            ->assertResponseStatus(200)
            ->assertSee('<title>Frontpage</title>');
    }
}
