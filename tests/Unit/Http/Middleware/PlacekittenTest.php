<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\Placekitten;
use App\Http\Request;
use App\Services\ConfigService;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\TestResponse;

class PlacekittenTest extends TestCase
{
    /**
     * @covers \App\Http\Middleware\Placekitten::handle
     */
    public function testDontPlacekitten(): void
    {
        $request = Request::create(ConfigService::getString('base_url') . '/');

        $next = function ($request) {
            return new Response('No kitten');
        };

        $middleware = new Placekitten();
        $response = $middleware->handle($request, $next);

        $response = new TestResponse($response);
        $response->assertSee('No kitten');
    }

    /**
     * @covers \App\Http\Middleware\Placekitten::handle
     */
    public function testPlacekitten(): void
    {
        $request = Request::create(ConfigService::getString('base_url') . '/images/404.jpg');

        $next = function ($request) {
            return new Response('No kitten');
        };

        $middleware = new Placekitten();
        $response = $middleware->handle($request, $next);

        $response = new TestResponse($response);
        $response->assertRedirect('https://placeimg.com/150/150/animals');
    }

    /**
     * @covers \App\Http\Middleware\Placekitten::handle
     */
    public function testPlacekittenKnownSize(): void
    {
        $request = Request::create(ConfigService::getString('base_url') . '/images/test.jpg');

        $next = function ($request) {
            return new Response('No kitten');
        };

        $middleware = new Placekitten();
        $response = $middleware->handle($request, $next);

        $response = new TestResponse($response);
        $response->assertRedirect('https://placeimg.com/128/64/animals');
    }
}
