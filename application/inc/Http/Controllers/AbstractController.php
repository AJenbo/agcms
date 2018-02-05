<?php namespace App\Http\Controllers;

use App\Render;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController
{
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
     * Add the needed headeres for a 304 cache response based on the loaded data.
     *
     * @param Response|null $response
     * @param int           $timestamp
     * @param int           $maxAge
     *
     * @return Response
     */
    protected function cachedResponse(Response $response = null, int $timestamp = null, int $maxAge = 0): Response
    {
        if (!$response) {
            $response = new Response();
        }

        $timestamp = $timestamp ?? $this->getUpdateTime();
        $lastModified = DateTime::createFromFormat('U', (string) $timestamp, new DateTimeZone('GMT'));
        if (!$lastModified) {
            return $response;
        }

        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->setLastModified($lastModified);
        $response->setMaxAge($maxAge);

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

        $dbTime = app('db')->dataAge();
        if ($dbTime) {
            $updateTime = max($dbTime, $updateTime ?: 0);
        }

        if ($updateTime <= 0) {
            return time();
        }

        return $updateTime;
    }
}
