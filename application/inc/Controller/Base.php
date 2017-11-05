<?php namespace AGCMS\Controller;

use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        $url = unparseUrl($url);

        return new RedirectResponse($url, $status);
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
        $redirectUrl = '/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=';
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
            $redirectUrl = '/?q=' . rawurlencode($query) . '&sogikke=&minpris=&maxpris=&maerke=0';
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

        return [
            'menu'           => $category->getVisibleChildren(),
            'infoPage'       => ORM::getOne(CustomPage::class, 2),
            'crumbs'         => [$category],
            'category'       => $category,
        ];
    }
}
