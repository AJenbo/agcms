<?php namespace AGCMS\Interfaces;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface Middleware
{
    /**
     * Process request and response before calling controller function.
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
