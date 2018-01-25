<?php namespace AGCMS\Tests\Feature\Controller;

use AGCMS\Tests\TestCase;

class SiteTest extends TestCase
{
    public function testFrontpage(): void
    {
        $this->call('GET', '/');
        $this->assertResponseStatus(200);
    }
}
