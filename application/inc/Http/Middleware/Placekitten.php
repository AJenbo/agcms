<?php

namespace App\Http\Middleware;

use App\Contracts\Middleware;
use App\Http\Request;
use App\Models\File;
use App\Services\ConfigService;
use Symfony\Component\HttpFoundation\Response;

class Placekitten implements Middleware
{
    /**
     * Generate a redirect if URL was not UTF-8 encoded.
     */
    public function handle(Request $request, callable $next): Response
    {
        $requestUrl = $request->getPathInfo();
        if (0 !== mb_strpos($requestUrl, '/files/') && 0 !== mb_strpos($requestUrl, '/images/')) {
            return $next($request);
        }

        $width = ConfigService::getInt('thumb_width');
        $height = ConfigService::getInt('thumb_height');

        $requestUrl = rawurldecode($request->getRequestUri());
        $file = File::getByPath($requestUrl);
        if ($file) {
            $width = $file->getWidth();
            $height = $file->getHeight();
        }

        $url = 'https://placeimg.com/' . $width . '/' . $height . '/animals';

        return redirect($url, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
