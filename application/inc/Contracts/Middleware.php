<?php namespace App\Contracts;

use App\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;

interface Middleware
{
    /**
     * Process request and response before calling controller function.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
