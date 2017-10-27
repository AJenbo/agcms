<?php

namespace AGCMS;

use AGCMS\Controller\Base;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application
{
    /** @var array[] */
    protected $routes = [];

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

    public function addRoute(string $method, string $uri, string $controller, string $action)
    {
        $this->routes[$method][] = ['url' => $uri, 'controller' => $controller, 'action' => $action];
    }

    public function run(Request $request): void
    {
        session_start();
        Render::sendCacheHeader();
        $response = $this->dispatch($request);
        $response->isNotModified(request()); // Set up 304 response if relevant
        $response->send();
    }

    private function dispatch(Request $request): Response
    {
        $requestUrl = urldecode($request->getRequestUri());

        foreach ($this->routes[$request->getMethod()] as $route) {
            if (preg_match('%^' . $route['url'] . '$%u', $requestUrl, $matches)) {
                $matches[0] = $request;
                return call_user_func_array([new $route['controller'](), $route['action']], $matches);
            }
        }

        return (new Base())->redirectToSearch($request);
    }
}
