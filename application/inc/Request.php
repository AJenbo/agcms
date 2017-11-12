<?php namespace AGCMS;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\ParameterBag;

class Request extends SymfonyRequest
{
    /**
     * Creates a new request with values from PHP's super globals.
     *
     * Also decode json in content data
     *
     * @return static
     */
    public static function createFromGlobals()
    {
        $request = parent::createFromGlobals();

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/json')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['POST', 'PUT', 'DELETE', 'PATCH'])
        ) {
            $data = json_decode($request->getContent(), true) ?? [];
            $data = is_array($data) ? $data : ['json' => $data];
            $request->request = new ParameterBag($data);
        }

        return $request;
    }
}
