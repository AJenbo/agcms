<?php namespace AGCMS;

class Config
{
    /**
     * Store the configurations.
     */
    private static $config = [];

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
        if (!self::$config) {
            $success = @include_once _ROOT_ . '/inc/config.php';
            if (!$success) {
                include_once _ROOT_ . '/inc/config_sample.php';
            }
            self::$config = $GLOBALS['_config'] ?? [];
            unset($GLOBALS['_config']);
        }

        return isset(self::$config[$key]) ? self::$config[$key] : $default;
    }
}
