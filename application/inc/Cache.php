<?php

class Cache
{
    private static $updateTime = 0;
    private static $loadedTables = [];

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
