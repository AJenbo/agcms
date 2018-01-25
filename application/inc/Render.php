<?php namespace AGCMS;

use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;
use Twig_Extensions_Extension_I18n;
use Twig_Loader_Filesystem;

class Render
{
    /** @var bool[] */
    private static $loadedTables = [];

    /** @var string[] */
    private static $adminOnlyTables = [
        'email',
        'emails',
        'fakturas',
        'newsmails',
        'PNL',
        'post',
        'template',
        'users',
    ];

    /**
     * Remember what tabels where read during page load.
     *
     * @param string $tableName The table name
     *
     * @return void
     */
    public static function addLoadedTable(string $tableName): void
    {
        self::$loadedTables[$tableName] = true;
    }

    /**
     * Figure out when the data for this page was last touched.
     *
     * @param bool $checkDb
     *
     * @return int
     */
    public static function getUpdateTime(bool $checkDb = true): int
    {
        $updateTime = 0;
        foreach (get_included_files() as $filename) {
            $updateTime = max($updateTime, filemtime($filename));
        }

        if ($checkDb) {
            $dbTime = db()->tablesUpdated(array_keys(self::$loadedTables), self::$adminOnlyTables);
            $updateTime = max($dbTime, $updateTime ?: 0);
        }

        if ($updateTime <= 0) {
            return time();
        }

        return $updateTime;
    }

    /**
     * Set Last-Modified and ETag http headers and use cache if no updates since last visit.
     *
     * @param Request  $request
     * @param int|null $timestamp Unix time stamp of last update to content
     *
     * @return ?Response
     */
    public static function sendCacheHeader(Request $request, int $timestamp = null): ?Response
    {
        if (!$request->isMethodCacheable()) {
            return null;
        }

        if (!$timestamp) {
            $timestamp = self::getUpdateTime();
        }
        if (!$timestamp) {
            return null;
        }

        $lastModified = DateTime::createFromFormat('U', (string) $timestamp);
        if (!$lastModified) {
            return null;
        }

        $response = new Response();
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->setLastModified($lastModified);
        $response->setMaxAge(0);

        if (!$response->isNotModified($request)) {
            return null;
        }

        return $response;
    }

    /**
     * Render a template.
     *
     * @param string $template
     * @param array  $data
     *
     * @return string
     */
    public static function render(string $template = 'index', array $data = []): string
    {
        $templatePath = app()->basePath('/theme');
        $loader = new Twig_Loader_Filesystem('default/', $templatePath);
        $langPath = 'default/' . config('locale', 'C') . '/';
        if (file_exists($templatePath . '/' . $langPath)) {
            $loader->prependPath($langPath);
        }
        if (config('theme')) {
            $loader->prependPath(config('theme') . '/');
            $langPath = config('theme') . '/' . config('locale', 'C') . '/';
            if (file_exists($templatePath . '/' . $langPath)) {
                $loader->prependPath($langPath);
            }
        }

        $twig = new Twig_Environment($loader);
        if ('production' === config('enviroment', 'develop')) {
            $twig->setCache(app()->basePath('/theme/cache/twig'));
        }
        if ('develop' === config('enviroment', 'develop')) {
            $twig->enableDebug();
        }
        $twig->addExtension(new Twig_Extensions_Extension_I18n());

        return $twig->render($template . '.html', $data);
    }
}
