<?php

namespace App\Services;

class ConfigService
{
    /** @var array<string, mixed> Store the configurations. */
    private static array $config = [];

    /**
     * Load the site configurations.
     *
     * Will fallback to config_sample.php if config.php does not exist.
     */
    public static function load(string $basePath): void
    {
        $config = @include $basePath . '/inc/config.php';
        if ($config === false) {
            $config = include $basePath . '/inc/config_sample.php';
        }
        self::$config = $config;
    }

    /**
     * Fetch a setting.
     *
     * @param mixed $default What to return if key does not exists
     *
     * @return mixed Key value
     */
    public static function get(string $key, $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }
}
