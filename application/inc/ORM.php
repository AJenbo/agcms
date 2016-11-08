<?php

class ORM
{
    private static $byId = [];
    private static $bySql = [];
    private static $oneBySql = [];

    public static function getOne(string $class, int $id)
    {
        if (!isset(self::$byId[$class][$id])) {
            self::$byId[$class][$id] = false;
            $data = db()->fetchOne("SELECT * FROM `" . $class::TABLE_NAME . "` WHERE id = " . $id);
            if ($data) {
                self::$byId[$class][$id] = new $class($class::mapFromDB($data));
            }
            Cache::addLoadedTable($class::TABLE_NAME);
        }

        return self::$byId[$class][$id];
    }

    public static function getOneByQuery(string $class, string $query)
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        if (!isset(self::$oneBySql[$class][$query])) {
            self::$oneBySql[$class][$query] = false;
            $data = db()->fetchOne($query);
            if ($data) {
                $entity = new $class($class::mapFromDB($data));
                self::$oneBySql[$class][$query] = $entity;
                self::$byId[$class][$entity->getId()] = $entity;
            }
            Cache::addLoadedTable($class::TABLE_NAME);
        }

        return self::$oneBySql[$class][$query];
    }

    public static function getByQuery(string $class, string $query): array
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        if (!isset(self::$bySql[$class][$query])) {
            self::$bySql[$class][$query] = [];
            foreach (db()->fetchArray($query) as $data) {
                $entity = new $class($class::mapFromDB($data));
                self::$bySql[$class][$query][] = $entity;
                self::$byId[$class][$entity->getId()] = $entity;
            }
            Cache::addLoadedTable($class::TABLE_NAME);
        }

        return self::$bySql[$class][$query];
    }
}
