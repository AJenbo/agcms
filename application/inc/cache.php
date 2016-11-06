<?php
/**
 * Declare the Cache class
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

/**
 * Helper classe for caching data in a simple array
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */
class Cache
{
    private static $cache = [];
    private static $updateTime = 0;
    private static $loadedTables = [];

    /**
     * @param string $key The cache key
     *
     * @return mixed
     */
    public static function get(string $key)
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        return null;
    }

    /**
     * @param string $key The cache key
     * @param mixed  $key The value to store, null will unset the key
     *
     * @return mixed
     */
    public static function set(string $key, $value)
    {
        if ($value === null) {
            unset(self::$cache[$key]);
            return;
        }

        self::$cache[$key] = $value;
    }

    /**
     * @param string $key The cache key
     *
     * @return mixed
     */
    public static function del(string $key)
    {
        unset(self::$cache[$key]);
    }

    /**
     * @param string $key The cache key
     * @param mixed  $key The value to store
     *
     * @return mixed
     */
    public static function addUpdateTime(int $timeStamp)
    {
        self::$updateTime = max(self::$updateTime, $timeStamp);
    }

    /**
     * @param string $tableName The table name
     */
    public static function addLoadedTable(string $tableName)
    {
        self::$loadedTables[$tableName] = true;
    }

    /**
     * @param string $tableName The table name
     */
    public static function getUpdateTime(bool $checkDb = true): int
    {
        foreach (get_included_files() as $filename) {
            self::$updateTime = max(self::$updateTime, filemtime($filename));
        }

        if ($checkDb) {
            $tables = db()->fetchArray("SHOW TABLE STATUS" . (self::$loadedTables ? " WHERE Name IN('" . implode("', '", array_keys(self::$loadedTables)) . "')" : ""));
            foreach ($tables as $table) {
                self::$updateTime = max(self::$updateTime, strtotime($table['Update_time']));
            }
        }

        if (self::$updateTime <= 0) {
            return time();
        }

        return self::$updateTime;
    }
}
