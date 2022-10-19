<?php

namespace App;

use App\Contracts\Middleware;
use App\Exceptions\Handler as ExceptionHandler;
use App\Http\Controllers\AbstractController;
use App\Http\Controllers\Base;
use App\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Application
{
    /** @var ?self */
    private static $instance;

    private string $basePath;

    /**
     * All of the global middleware for the application.
     *
     * @var array<class-string<Middleware>>
     */
    protected array $middleware = [];

    /** @var array<string, array<int, Route>> */
    private array $routes = [];

    /** @var array<class-string<object>, object> */
    private array $services = [];

    /**
     * Set up the enviroment.
     */
    public function __construct(string $basePath)
    {
        self::$instance = $this;
        $this->services[self::class] = $this;
        $this->basePath = $basePath;

        $this->initErrorLogging();
        $this->setLocale();
        $this->loadTranslations();
        $this->loadRoutes();
    }

    public function environment(string ...$environments): bool
    {
        foreach ($environments as $environment) {
            return $environment === config('enviroment', 'develop');
        }

        return false;
    }

    /**
     * Set error loggin.
     */
    private function initErrorLogging(): void
    {
        if ($this->environment('develop')) {
            ini_set('display_errors', '1');
            error_reporting(-1);
        }
    }

    /**
     * Set locale and endcodings.
     */
    private function setLocale(): void
    {
        date_default_timezone_set(config('timezone', 'Europe/Copenhagen'));

        setlocale(LC_ALL, config('locale', 'C'));
        setlocale(LC_NUMERIC, 'C');

        mb_language('uni');
        mb_detect_order('UTF-8, ISO-8859-1');
        mb_internal_encoding('UTF-8');
    }

    /**
     * Load translations.
     */
    private function loadTranslations(): void
    {
        bindtextdomain('app', $this->basePath . '/theme/locale');
        bind_textdomain_codeset('app', 'UTF-8');
        textdomain('app');
    }

    /**
     * Load application routes.
     */
    private function loadRoutes(): void
    {
        Routes::load($this);
    }

    /**
     * Get base path for the running application.
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . $path;
    }

    /**
     * Get the most recent instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        return new self(realpath(__DIR__ . '/../..') ?: '');
    }

    /**
     * Gets a service.
     *
     * @template T of object
     *
     * @param class-string<T> $id The service identifier
     *
     * @return T The associated service
     */
    public function get(string $id): object
    {
        if (!isset($this->services[$id])) {
            $this->services[$id] = new $id();
        }

        /** @var T */
        $class = $this->services[$id];
        assert(is_object($class));

        return $class;
    }

    /**
     * Add new middleware to the application.
     *
     * @param array<class-string<Middleware>>|class-string<Middleware> $middleware
     *
     * @return $this
     */
    public function middleware($middleware): self
    {
        $middleware = (array)$middleware;

        $this->middleware = array_unique(array_merge($this->middleware, $middleware));

        return $this;
    }

    /**
     * Add a route.
     *
     * @param class-string<AbstractController> $controller
     */
    public function addRoute(string $method, string $uri, string $controller, string $action): void
    {
        $this->routes[$method][] = new Route($uri, $controller, $action);
    }

    /**
     * Run the application.
     */
    public function run(): void
    {
        $request = Request::createFromGlobals();
        $response = $this->handle($request);
        $response->send();
    }

    /**
     * Handle a request.
     */
    public function handle(Request $request): Response
    {
        $this->services[Request::class] = $request;

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
     * Handle the given exception.
     */
    protected function handleException(Request $request, Throwable $exception): Response
    {
        $handler = $this->get(ExceptionHandler::class);

        $handler->report($exception);

        return $handler->render($request, $exception);
    }

    /**
     * Find a matching route for the current request.
     */
    private function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $requestUrl = rawurldecode($request->getPathInfo());
        $processRequest = $this->matchRoute($method, $requestUrl);

        foreach ($this->middleware as $middleware) {
            $processRequest = $this->wrapMiddleware($middleware, $processRequest);
        }

        return $processRequest($request);
    }

    /**
     * Wrap closure in a middle ware call.
     *
     * @return callable(Request): Response
     */
    private function matchRoute(string $method, string $requestUrl): callable
    {
        if ('HEAD' === $method) {
            $method = 'GET';
        }

        foreach ($this->routes[$method] as $route) {
            if (preg_match('%^' . $route->getUri() . '$%u', $requestUrl, $matches)) {
                return function (Request $request) use ($route, $matches): Response {
                    $matches[0] = $request;

                    $class = $route->getController();
                    /** @var callable(Request, string...) */
                    $callable = [new $class(), $route->getAction()];

                    return $callable(...$matches);
                };
            }

            if (preg_match('%^' . $route->getUri() . '$%u', $requestUrl . '/', $matches)) {
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
     * @param class-string<Middleware>    $middlewareClass
     * @param callable(Request): Response $next
     *
     * @return callable(Request): Response
     */
    private function wrapMiddleware(string $middlewareClass, callable $next): callable
    {
        return function (Request $request) use ($middlewareClass, $next): Response {
            return (new $middlewareClass())->handle($request, $next);
        };
    }

    /**
     * Generate a redirect for the requested path with a / appended to the path.
     *
     * @return callable(Request): Response
     */
    private function redirectToFolderPath(string $requestUrl): callable
    {
        return function (Request $request) use ($requestUrl): RedirectResponse {
            $query = $request->getQueryString() ?: '';
            if ($query) {
                $query = '?' . $query;
            }

            return redirect($requestUrl . '/' . $query, Response::HTTP_PERMANENTLY_REDIRECT);
        };
    }
}
