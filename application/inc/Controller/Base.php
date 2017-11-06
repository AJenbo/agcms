<?php namespace AGCMS\Controller;

use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\ORM;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Base
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
     * Generate a redirect to the search page based on the current request url
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function redirectToSearch(Request $request): RedirectResponse
    {
        $redirectUrl = '/search/';

        $query = preg_replace(
            [
                '/\/|-|_|\.html|\.htm|\.php|\.gif|\.jpeg|\.jpg|\.png|mærke[0-9]+-|kat[0-9]+-|side[0-9]+-|\.php/u',
                '/[^\w0-9]/u',
                '/([0-9]+)/u',
                '/([[:upper:]]?[[:lower:]]+)/u',
                '/\s+/u',
            ],
            [
                ' ',
                ' ',
                ' \1 ',
                ' \1',
                ' ',
            ],
            urldecode($request->getPathInfo())
        );
        $query = trim($query);
        if ($query) {
            $redirectUrl = '/search/results/?q=' . rawurlencode($query) . '&sogikke=&minpris=&maxpris=&maerke=0';
        }

        return $this->redirect($request, $redirectUrl);
    }

    /**
     * Get the basice render data
     *
     * @return array
     */
    protected function basicPageData(): array
    {
        /** @var Category */
        $category = ORM::getOne(Category::class, 0);
        assert($category instanceof Category);

        return [
            'menu'           => $category->getVisibleChildren(),
            'infoPage'       => ORM::getOne(CustomPage::class, 2),
            'crumbs'         => [$category],
            'category'       => $category,
        ];
    }
}
