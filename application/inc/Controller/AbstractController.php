<?php namespace AGCMS\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class AbstractController
{
    /**
     * Generate redirect response
     *
     * @param Request $request
     * @param string  $url
     * @param int     $status
     *
     * @return RedirectResponse
     */
    public function redirect(Request $request, string $url, int $status = Response::HTTP_SEE_OTHER): RedirectResponse
    {
        $url = parse_url($url);
        if (empty($url['scheme'])) {
            $url['scheme'] = $request->getScheme();
        }
        if (empty($url['host'])) {
            $url['host'] = $request->getHost();
        }
        if (empty($url['path'])) {
            $url['path'] = urldecode($request->getPathInfo());
        } elseif ('/' !== mb_substr($url['path'], 0, 1)) {
            //The redirect is relative to current path
            $path = [];
            $requestPath = urldecode($request->getPathInfo());
            preg_match('#^.+/#u', $requestPath, $path);
            $url['path'] = $path[0] . $url['path'];
        }
        $url['path'] = encodeUrl($url['path']);
        $url = $this->unparseUrl($url);

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
}
