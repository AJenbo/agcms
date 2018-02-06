<?php namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

class SearchTest extends TestCase
{
    public function testIndex(): void
    {
        $this->get('/search/')
            ->assertResponseStatus(200)
            ->assertSee('<form action="/search/results/" method="get">')
            ->assertSee('<option value="1">Test</option>');
    }

    public function testResultsBrandRedirect(): void
    {
        $this->get('/search/results/?q=&varenr=&sogikke=&minpris=&maxpris=&maerke=1')
            ->assertResponseStatus(301)
            ->assertRedirect('/m%C3%A6rke1-Test/');
    }

    public function testResultsBrandRedirectInactive(): void
    {
        $this->get('/search/results/?q=&varenr=&sogikke=&minpris=&maxpris=&maerke=2')
            ->assertResponseStatus(301)
            ->assertRedirect('/search/');
    }
}
