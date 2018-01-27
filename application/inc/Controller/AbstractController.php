<?php namespace AGCMS\Controller;

use AGCMS\Render;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController
{
    /**
     * Generate redirect response.
     *
     * @param Request $request
     * @param string  $url
     * @param int     $status
     *
     * @return RedirectResponse
     */
    public function redirect(Request $request, string $url, int $status = Response::HTTP_SEE_OTHER): RedirectResponse
    {
        $urlComponent = parse_url($url);
        if (empty($urlComponent['scheme'])) {
            $urlComponent['scheme'] = $request->getScheme();
        }
        if (empty($urlComponent['host'])) {
            $urlComponent['host'] = $request->getHost();
        }
        if (empty($urlComponent['path'])) {
            $urlComponent['path'] = urldecode($request->getPathInfo());
        } elseif ('/' !== mb_substr($urlComponent['path'], 0, 1)) {
            //The redirect is relative to current path
            $path = [];
            $requestPath = urldecode($request->getPathInfo());
            preg_match('#^.+/#u', $requestPath, $path);
            $urlComponent['path'] = $path[0] . $urlComponent['path'];
        }
        $urlComponent['path'] = encodeUrl($urlComponent['path']);
        $url = $this->unparseUrl($urlComponent);

        return new RedirectResponse($url, $status);
    }

    /**
     * Build a url string from an array.
     *
     * @param array $parsedUrl Array as returned by parse_url()
     *
     * @return string The URL
     */
    private function unparseUrl(array $parsedUrl): string
    {
        $scheme = !empty($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = !empty($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port = !empty($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user = !empty($parsedUrl['user']) ? $parsedUrl['user'] : '';
        $pass = !empty($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
        $pass .= ($user || $pass) ? '@' : '';
        $path = !empty($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query = !empty($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = !empty($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
    }

    /**
     * Renders a view.
     *
     * @param string   $view
     * @param array    $parameters
     * @param Response $response
     *
     * @return Response
     */
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $content = app('render')->render($view, $parameters);

        if (null === $response) {
            $response = new Response();
        }
        $response->setContent($content);

        return $response;
    }

    /**
     * Generate an early 304 Response if posible.
     *
     * @param Request $request
     *
     * @return ?Response
     */
    protected function earlyResponse(Request $request): ?Response
    {
        if ($request->headers->has('Last-Modefied')) {
            $response = $this->cachedResponse();
            if ($response->isNotModified($request)) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Add the needed headeres for a 304 cache response based on the loaded data.
     *
     * @param Response|null $response
     *
     * @return Response
     */
    protected function cachedResponse(Response $response = null): Response
    {
        if (!$response) {
            $response = new Response();
        }

        $timestamp = $this->getUpdateTime();
        $lastModified = DateTime::createFromFormat('U', (string) $timestamp);
        if (!$lastModified) {
            return $response;
        }

        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->setLastModified($lastModified);
        $response->setMaxAge(0);

        return $response;
    }

    /**
     * Figure out when the loaded data was last touched.
     *
     * @return int
     */
    private function getUpdateTime(): int
    {
        $updateTime = 0;
        foreach (get_included_files() as $filename) {
            $updateTime = max($updateTime, filemtime($filename));
        }

        if ($checkDb) {
            $dbTime = app('db')->dataAge(static::ADMIN_ONLY_TABLES);
            $updateTime = max($dbTime, $updateTime ?: 0);
        }

        if ($updateTime <= 0) {
            return time();
        }

        return $updateTime;
    }
}
