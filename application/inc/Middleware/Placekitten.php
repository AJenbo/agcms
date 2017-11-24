<?php namespace AGCMS\Middleware;

use AGCMS\Config;
use AGCMS\Controller\Base;
use AGCMS\Entity\File;
use AGCMS\Interfaces\Middleware;
use AGCMS\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;

class Placekitten implements Middleware
{
    /**
     * Generate a redirect if URL was not UTF-8 encoded.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $width = Config::get('thumb_width');
        $height = Config::get('thumb_height');

        /** @var File */
        $requestUrl = urldecode($request->getRequestUri());
        $file = File::getByPath($requestUrl);
        if ($file) {
            $width = $file->getWidth();
            $height = $file->getHeight();
        }

        $url = 'https://placeimg.com/' . $width . '/' . $height . '/animals';

        return (new Base())->redirect($request, $url, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
