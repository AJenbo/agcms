<?php

namespace AGCMS;

use Symfony\Component\HttpFoundation\Request;

class Application
{
    /** @var self */
    protected static $instance;

    public function __construct(string $basePath)
    {
        date_default_timezone_set(Config::get('timezone', 'Europe/Copenhagen'));

        if ('develop' === Config::get('enviroment', 'develop')) {
            ini_set('display_errors', 1);
            error_reporting(-1);
        }

        setlocale(LC_ALL, Config::get('locale', 'C'));
        setlocale(LC_NUMERIC, 'C');

        bindtextdomain('agcms', $basePath . '/theme/locale');
        bind_textdomain_codeset('agcms', 'UTF-8');
        textdomain('agcms');

        mb_language('uni');
        mb_detect_order('UTF-8, ISO-8859-1');
        mb_internal_encoding('UTF-8');

        session_cache_limiter('');

        defined('_ROOT_') || define('_ROOT_', $basePath);
        $this->basePath = $basePath;
        self::$instance = $this;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function run(Request $request): void
    {
        session_start();
        Render::sendCacheHeader();
        $this->dispatch($request);
        Render::outputPage();
    }

    public function dispatch(Request $request): void
    {
        Render::doRouting($request);
    }
}
