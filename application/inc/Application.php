<?php namespace AGCMS;

use AGCMS\Controller\Base;
use Raven_Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Application
{
    /** @var string */
    protected $basePath;

    /** @var array[] */
    protected $routes = [];

    /**
     * Set up the enviroment.
     *
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
     * Add a route.
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
     * Run the application.
     *
     * @param Request $request
     *
     * @return void
     */
    public function run(Request $request): void
    {
        $ravenClient = new Raven_Client(Config::get('sentry'));
        $ravenClient->install();

        Render::sendCacheHeader($request);
        try {
            $response = $this->dispatch($request);
        } catch (Throwable $exception) {
            $ravenClient->captureException($exception);
            $response = new Response($exception->getMessage());
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $response->prepare($request);
        $response->isNotModified($request); // Set up 304 response if relevant
        $response->send();
    }

    /**
     * Find a matching route for the current request.
     *
     * @param Request $request
     *
     * @return Response
     */
    private function dispatch(Request $request): Response
    {
        $redirect = $this->correctEncoding($request);
        if ($redirect) {
            return $redirect;
        }

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

    /**
     * Generate a redirect if URL was not UTF-8 encoded.
     *
     * @param Request $request
     *
     * @return ?RedirectResponse
     */
    private function correctEncoding(Request $request): ?RedirectResponse
    {
        $requestUrl = urldecode($request->getRequestUri());

        $encoding = mb_detect_encoding($requestUrl, 'UTF-8, ISO-8859-1');
        if ('UTF-8' === $encoding) {
            return null;
        }

        // Windows-1252 is a superset of iso-8859-1
        if (!$encoding || 'ISO-8859-1' === $encoding) {
            $encoding = 'windows-1252';
        }

        $requestUrl = mb_convert_encoding($requestUrl, 'UTF-8', $encoding);

        return (new Base())->redirect($request, $requestUrl, Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Generate a redirect for the requested path with a / appended to the path.
     *
     * @param Request $request
     * @param string  $requestUrl
     *
     * @return RedirectResponse
     */
    private function redirectToFolderPath(Request $request, string $requestUrl): RedirectResponse
    {
        $query = $request->getQueryString() ?: '';
        if ($query) {
            $query = '?' . $query;
        }

        return (new Base())->redirect($request, $requestUrl . '/' . $query, Response::HTTP_PERMANENTLY_REDIRECT);
    }
}
