<?php namespace App;

use App\Exceptions\Handler as ExceptionHandler;
use App\Http\Controllers\Base;
use App\Http\Request;
use App\Services\DbService;
use App\Services\OrmService;
use App\Services\RenderService;
use Closure;
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

    /** @var string[] */
    private $aliases = [
        'db'     => DbService::class,
        'orm'    => OrmService::class,
        'render' => RenderService::class,
    ];

    /** @var object[] */
    private $services = [];

    /**
     * Set up the enviroment.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;

        $this->initErrorLogging();
        $this->setLocale();
        $this->loadTranslations();
        $this->loadRoutes();

        self::$instance = $this;
    }

    /**
     * Set error loggin.
     *
     * @return void
     */
    private function initErrorLogging(): void
    {
        if ('develop' === config('enviroment', 'develop')) {
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
        if (!self::$instance) {
            new self(realpath(__DIR__ . '/../..'));
        }

        return self::$instance;
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
        $id = $this->aliases[$id] ?? $id;

        if (!isset($this->services[$id])) {
            $this->services[$id] = new $id();
        }

        return $this->services[$id];
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

            return redirect($requestUrl . '/' . $query, Response::HTTP_PERMANENTLY_REDIRECT);
        };
    }
}
