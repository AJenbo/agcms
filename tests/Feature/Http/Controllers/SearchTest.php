<?php namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class SearchTest extends TestCase
{
    public function testIndex(): void
    {
        $this->json('GET', '/search/')
            ->assertResponseStatus(200)
            ->assertSee('<form action="/search/results/" method="get">')
            ->assertSee('<option value="1">Test</option>');
    }

    public function testResultsBrandRedirect(): void
    {
        $this->json('GET', '/search/results/?q=&varenr=&sogikke=&minpris=&maxpris=&maerke=1')
            ->assertResponseStatus(301)
            ->assertRedirect('/m%C3%A6rke1-Test/');
    }

    public function testResultsBrandRedirectInactive(): void
    {
        $this->json('GET', '/search/results/?q=&varenr=&sogikke=&minpris=&maxpris=&maerke=2')
            ->assertResponseStatus(301)
            ->assertRedirect('/search/');
    }
}
