<?php namespace App\Http\Middleware;

use App\Contracts\Middleware;
use App\Http\Controllers\Base;
use App\Models\File;
use App\Http\Request;
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
        $width = config('thumb_width');
        $height = config('thumb_height');

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
