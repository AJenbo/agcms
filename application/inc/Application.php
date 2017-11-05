<?php

namespace AGCMS;

use Throwable;
use AGCMS\Controller\Base;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Application
{
    /** @var array[] */
    protected $routes = [];

    /**
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        date_default_timezone_set(Config::get('timezone', 'Europe/Copenhagen'));

        if ('develop' === Config::get('enviroment', 'develop')) {
            ini_set('display_errors', 1);
            error_reporting(-1);
        }

        setlocale(LC_ALL, Config::get('locale', 'C'));
        setlocale(LC_NUMERIC, 'C');

        bindtextdomain('agcms', $basePath . '/theme/locale');
        bind_textdomain_codeset('agcms', 'UTF-8');
        textdomain('agcms');

        mb_language('uni');
        mb_detect_order('UTF-8, ISO-8859-1');
        mb_internal_encoding('UTF-8');

        session_cache_limiter('');

        defined('_ROOT_') || define('_ROOT_', $basePath);
        $this->basePath = $basePath;
    }

    /**
     * Add a route
     *
     * @param string $method
     * @param string $uri
     * @param string $controller
     * @param string $action
     *
     * @return void
     */
    public function addRoute(string $method, string $uri, string $controller, string $action): void
    {
        $this->routes[$method][] = ['url' => $uri, 'controller' => $controller, 'action' => $action];
    }

    /**
     * Run the application
     *
     * @param Request $request
     *
     * @return void
     */
    public function run(Request $request): void
    {
        session_start();
        Render::sendCacheHeader();
        try {
            $response = $this->dispatch($request);
        } catch (Throwable $exception) {
            $response = new Response($exception->getMessage());
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $response->isNotModified(request()); // Set up 304 response if relevant
        $response->send();
    }

    /**
     * Find a matching route for the current request
     *
     * @param Request $request
     *
     * @return Response
     */
    private function dispatch(Request $request): Response
    {

        $requestUrl = urldecode($request->getPathInfo());
        foreach ($this->routes[$request->getMethod()] as $route) {
            if (preg_match('%^' . $route['url'] . '$%u', $requestUrl, $matches)) {
                $matches[0] = $request;
                return call_user_func_array([new $route['controller'](), $route['action']], $matches);
            }

            if (preg_match('%^' . $route['url'] . '$%u', $requestUrl . '/', $matches)) {
                return $this->redirectToFolderPath($request, $requestUrl);
            }
        }

        return (new Base())->redirectToSearch($request);
    }

    private function redirectToFolderPath(Request $request, string $requestUrl): RedirectResponse
    {
        $query = $request->getQueryString();
        if ($query) {
            $query = '?' . $query;
        }

        return (new Base())->redirect($request, $requestUrl . '/' . $query, Response::HTTP_PERMANENTLY_REDIRECT);
    }
}
