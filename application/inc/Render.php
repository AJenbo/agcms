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
            $updateTime = self::checkDbUpdate($updateTime ?: 0);
        }

        if ($updateTime <= 0) {
            return time();
        }

        return $updateTime;
    }

    /**
     * Check update time for tables in database.
     *
     * @param int $updateTime
     *
     * @return int
     */
    private static function checkDbUpdate(int $updateTime): int
    {
        $timeOffset = db()->getTimeOffset();
        $where = ' WHERE 1';
        if (self::$adminOnlyTables) {
            $where .= " AND Name NOT IN('" . implode("', '", self::$adminOnlyTables) . "')";
        }
        if (self::$loadedTables) {
            $where .= " AND Name IN('" . implode("', '", array_keys(self::$loadedTables)) . "')";
        }
        $tables = db()->fetchArray('SHOW TABLE STATUS' . $where);
        foreach ($tables as $table) {
            $updateTime = max($updateTime, strtotime($table['Update_time']) + $timeOffset);
        }

        return $updateTime;
    }

    /**
     * Set Last-Modified and ETag http headers and use cache if no updates since last visit.
     *
     * @param Request  $request
     * @param int|null $timestamp Unix time stamp of last update to content
     *
     * @return void
     */
    public static function sendCacheHeader(Request $request, int $timestamp = null): void
    {
        if (!$request->isMethodCacheable()) {
            return;
        }

        if (!$timestamp) {
            $timestamp = self::getUpdateTime();
        }
        if (!$timestamp) {
            return;
        }

        $lastModified = DateTime::createFromFormat('U', (string) $timestamp);
        if (!$lastModified) {
            return;
        }

        $response = new Response();
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->setLastModified($lastModified);
        $response->setMaxAge(0);

        if ($response->isNotModified($request)) {
            $response->send();
            exit;
        }
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
        $templatePath = app()->basePath('/theme/');
        $loader = new Twig_Loader_Filesystem('default/', $templatePath);
        if ('en_US' !== Config::get('locale', 'en_US')) {
            $loader->prependPath('default/' . Config::get('locale') . '/');
        }
        if (Config::get('theme')) {
            $loader->prependPath(Config::get('theme') . '/');
            if ('en_US' !== Config::get('locale', 'en_US')) {
                $loader->prependPath(Config::get('theme') . '/' . Config::get('locale') . '/');
            }
        }

        $twig = new Twig_Environment($loader);
        if ('production' === Config::get('enviroment', 'develop')) {
            $twig->setCache(app()->basePath('/theme/cache/twig'));
        }
        if ('develop' === Config::get('enviroment', 'develop')) {
            $twig->enableDebug();
        }
        $twig->addExtension(new Twig_Extensions_Extension_I18n());

        return $twig->render($template . '.html', $data);
    }
}
