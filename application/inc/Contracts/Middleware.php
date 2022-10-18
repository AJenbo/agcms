<?php

namespace App\Contracts;

use App\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface Middleware
{
    /**
     * Process request and response before calling controller function.
     *
     * @param callable(Request): Response $next
     */
    public function handle(Request $request, callable $next): Response;
}
