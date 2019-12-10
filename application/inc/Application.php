<?php namespace App;

use App\Exceptions\Handler as ExceptionHandler;
use App\Http\Controllers\Base;
use App\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Application
{
    /** @var ?self */
    private static $instance;

    /** @var string */
    private $basePath;

    /**
     * All of the global middleware for the application.
     *
     * @var string[]
     */
    protected $middleware = [];

    /** @var array<string, null|array<string, array<string, string>>> */
    private $routes = [];

    /** @var object[] */
    private $services = [];

    /**
     * Set up the enviroment.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        self::$instance = $this;
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
     *
     * @return void
     */
    private function initErrorLogging(): void
    {
        if ($this->environment('develop')) {
            ini_set('display_errors', 1);
            error_reporting(-1);
        }
    }

    /**
     * Set locale and endcodings.
     *
     * @return void
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
     *
     * @return void
     */
    private function loadTranslations(): void
    {
        bindtextdomain('app', $this->basePath . '/theme/locale');
        bind_textdomain_codeset('app', 'UTF-8');
        textdomain('app');
    }

    /**
     * Load application routes.
     *
     * @return void
     */
    private function loadRoutes(): void
    {
        $app = $this;
        require __DIR__ . '/routes.php';
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

    /**
     * Get the most recent instance.
     *
     * @return self
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
     * @param string $id The service identifier
     *
     * @return object The associated service
     */
    public function get(string $id)
    {
        if (!isset($this->services[$id])) {
            $this->services[$id] = new $id();
        }

        return $this->services[$id];
    }

    /**
     * Add new middleware to the application.
     *
     * @param string|string[] $middleware
     *
     * @return $this
     */
    public function middleware($middleware): self
    {
        $middleware = (array) $middleware;

        $this->middleware = array_unique(array_merge($this->middleware, $middleware));

        return $this;
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
     *
     * @param Request   $request
     * @param Throwable $exception
     *
     * @return Response
     */
    protected function handleException(Request $request, Throwable $exception): Response
    {
        /** @var ExceptionHandler */
        $handler = $this->get(ExceptionHandler::class);

        $handler->report($exception);

        return $handler->render($request, $exception);
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
     * @param string $method
     * @param string $requestUrl
     *
     * @return Closure
     */
    private function matchRoute(string $method, string $requestUrl): Closure
    {
        if ('HEAD' === $method) {
            $method = 'GET';
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match('%^' . $route['url'] . '$%u', $requestUrl, $matches)) {
                return function (Request $request) use ($route, $matches): Response {
                    $matches[0] = $request;

                    /** @var callable */
                    $callable = [new $route['controller'](), $route['action']];
                    return call_user_func_array($callable, $matches);
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

            return redirect($requestUrl . '/' . $query, Response::HTTP_PERMANENTLY_REDIRECT);
        };
    }
}
