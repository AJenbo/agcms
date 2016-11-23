<?php

class ORM
{
    /**
     * Cache entity by id
     */
    private static $byId = [];

    /**
     * Cache multiple entity by query
     */
    private static $bySql = [];

    /**
     * Cache entity by query
     */
    private static $oneBySql = [];

    /**
     * Get a single entitly by id
     *
     * @param string $class Class name
     * @param int    $id    Id of the entity
     *
     * @return ?AbstractEntity
     */
    public static function getOne(string $class, int $id)
    {
        if (!isset(self::$byId[$class][$id])) {
            self::$byId[$class][$id] = false;
            $data = db()->fetchOne("SELECT * FROM `" . $class::TABLE_NAME . "` WHERE id = " . $id);
            if ($data) {
                self::$byId[$class][$id] = new $class($class::mapFromDB($data));
            }
            Render::addLoadedTable($class::TABLE_NAME);
        }

        return self::$byId[$class][$id];
    }

    /**
     * Find a single entity from a SQL query string
     *
     * @param string $class Class name
     * @param string $query The query
     *
     * @return ?AbstractEntity
     */
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
            Render::addLoadedTable($class::TABLE_NAME);
        }

        return self::$oneBySql[$class][$query];
    }

    /**
     * Find multiple entities from a SQL query string
     *
     * @param string $class Class name
     * @param string $query The query
     *
     * @return array
     */
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
            Render::addLoadedTable($class::TABLE_NAME);
        }

        return self::$bySql[$class][$query];
    }

    /**
     * Remove an entity from the caches
     *
     * @param string $class
     * @param int $id
     *
     * @return
     */
    public static function forget(string $class, int $id)
    {
        unset(self::$byId[$class][$id], self::$bySql, self::$oneBySql);
    }
}
