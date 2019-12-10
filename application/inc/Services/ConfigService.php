<?php namespace App\Services;

class ConfigService
{
    /** @var array<string, mixed> Store the configurations. */
    private static $config = [];

    /**
     * Load the site configurations.
     *
     * Will fallback to config_sample.php if config.php does not exist.
     *
     * @param string $basePath
     *
     * @return void
     */
    public static function load(string $basePath): void
    {
        self::$config = @include $basePath . '/inc/config.php';
        if (!self::$config) {
            self::$config = include $basePath . '/inc/config_sample.php';
        }
    }

    /**
     * Fetch a setting.
     *
     * @param string $key     The name of the configuration to fetch
     * @param mixed  $default What to return if key does not exists
     *
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }
}
