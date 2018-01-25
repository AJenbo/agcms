<?php namespace AGCMS;

use AGCMS\Controller\Base;
use AGCMS\Exception\InvalidInput;
use Closure;
use Raven_Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Application
{
    /** @var self */
    private static $instance;

    /** @var string */
    private $basePath;

    /** @var array[] */
    private $middleware = [];

    /** @var array[] */
    private $routes = [];

    /** @var Raven_Client */
    private $ravenClient;

    /** @var string[] */
    private $dontReport = [
        InvalidInput::class,
    ];

    /**
     * Set up the enviroment.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->ravenClient = new Raven_Client(config('sentry'));
        $this->ravenClient->install();

        date_default_timezone_set(config('timezone', 'Europe/Copenhagen'));

        if ('develop' === config('enviroment', 'develop')) {
            ini_set('display_errors', 1);
            error_reporting(-1);
        }

        setlocale(LC_ALL, config('locale', 'C'));
        setlocale(LC_NUMERIC, 'C');

        bindtextdomain('agcms', $basePath . '/theme/locale');
        bind_textdomain_codeset('agcms', 'UTF-8');
        textdomain('agcms');

        mb_language('uni');
        mb_detect_order('UTF-8, ISO-8859-1');
        mb_internal_encoding('UTF-8');

        $this->basePath = $basePath;

        self::$instance = $this;
    }

    /**
     * Get base path for the running application.
     *
     * @param string $path
     *
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . $path;
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            new self(realpath(__DIR__ . '/../..'));
        }

        return self::$instance;
    }

    /**
     * Add middleware.
     *
     * @param string $uriPrefix
     * @param string $middleware
     *
     * @return void
     */
    public function addMiddleware(string $uriPrefix, string $middleware): void
    {
        $this->middleware[] = ['uriPrefix' => $uriPrefix, 'middleware' => $middleware];
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
     * @return void
     */
    public function run(): void
    {
        $request = Request::createFromGlobals();
        $response = $this->handle($request);
        $response->send();
    }

    /**
     * Handle a request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request): Response
    {
        Render::sendCacheHeader($request);

        try {
            $response = $this->dispatch($request);
        } catch (Throwable $exception) {
            $response = $this->handleException($request, $exception);
        }
        $response->prepare($request);
        $response->isNotModified($request); // Set up 304 response if relevant

        return $response;
    }

    /**
     * Log an exception for later debugging.
     *
     * @param Throwable $exception
     *
     * @return ?string
     */
    public function logException(Throwable $exception): ?string
    {
        return $this->ravenClient->captureException($exception);
    }

    /**
     * Generate an error response and repport the exception.
     *
     * @param Request   $request
     * @param Throwable $exception
     *
     * @throws Throwable
     *
     * @return Response
     */
    private function handleException(Request $request, Throwable $exception): Response
    {
        $logId = null;
        if ($this->shouldLog($exception)) {
            if ('develop' === config('enviroment')) {
                http_response_code(Response::HTTP_INTERNAL_SERVER_ERROR);

                throw $exception;
            }

            if ($request->user()) {
                $this->ravenClient->user_context(
                    ['id' => $request->user()->getId(), 'name' => $request->user()->getFullName()]
                );
            }
            $logId = $this->logException($exception);
        }

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception->getCode() >= 400 && $exception->getCode() <= 599) {
            $status = $exception->getCode();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(
                ['error' => ['message' => $exception->getMessage(), 'sentry_id' => $logId]],
                $status
            );
        }

        return new Response($exception->getMessage(), $status);
    }

    /**
     * Determin if the exception should be logged.
     *
     * @param Throwable $exception
     *
     * @return bool
     */
    private function shouldLog(Throwable $exception): bool
    {
        foreach ($this->dontReport as $className) {
            if ($exception instanceof $className) {
                return false;
            }
        }

        return true;
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
        $metode = $request->getMethod();
        $requestUrl = urldecode($request->getPathInfo());
        $processRequest = $this->matchRoute($metode, $requestUrl);

        foreach ($this->middleware as $middleware) {
            if (0 === mb_strpos($requestUrl, $middleware['uriPrefix'])) {
                $processRequest = $this->wrapMiddleware($middleware['middleware'], $processRequest);
            }
        }

        return $processRequest($request);
    }

    /**
     * Wrap closure in a middle ware call.
     *
     * @param string $metode
     * @param string $requestUrl
     *
     * @return Closure
     */
    private function matchRoute(string $metode, string $requestUrl): Closure
    {
        if ('HEAD' === $metode) {
            $metode = 'GET';
        }

        foreach ($this->routes[$metode] ?? [] as $route) {
            if (preg_match('%^' . $route['url'] . '$%u', $requestUrl, $matches)) {
                return function (Request $request) use ($route, $matches): Response {
                    $matches[0] = $request;

                    return call_user_func_array([new $route['controller'](), $route['action']], $matches);
                };
            }

            if (preg_match('%^' . $route['url'] . '$%u', $requestUrl . '/', $matches)) {
                return $this->redirectToFolderPath($requestUrl);
            }
        }

        return function (Request $request): RedirectResponse {
            return (new Base())->redirectToSearch($request);
        };
    }

    /**
     * Wrap closure in a middle ware call.
     *
     * @param string  $middlewareClass
     * @param Closure $next
     *
     * @return Closure
     */
    private function wrapMiddleware(string $middlewareClass, Closure $next): Closure
    {
        return function (Request $request) use ($middlewareClass, $next): Response {
            return (new $middlewareClass())->handle($request, $next);
        };
    }

    /**
     * Generate a redirect for the requested path with a / appended to the path.
     *
     * @param string $requestUrl
     *
     * @return Closure
     */
    private function redirectToFolderPath(string $requestUrl): Closure
    {
        return function (Request $request) use ($requestUrl): RedirectResponse {
            $query = $request->getQueryString() ?: '';
            if ($query) {
                $query = '?' . $query;
            }

            return (new Base())->redirect($request, $requestUrl . '/' . $query, Response::HTTP_PERMANENTLY_REDIRECT);
        };
    }
}
