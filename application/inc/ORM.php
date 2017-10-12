<?php namespace AGCMS;

use AGCMS\Entity\AbstractEntity;

class ORM
{
    /**
     * Cache entity by id.
     */
    private static $byId = [];

    /**
     * Cache multiple entity by query.
     */
    private static $bySql = [];

    /**
     * Cache entity by query.
     */
    private static $oneBySql = [];

    /**
     * Get a single entitly by id.
     *
     * @param string $class Class name
     * @param int    $id    Id of the entity
     *
     * @return ?AbstractEntity
     */
    public static function getOne(string $class, int $id): ?AbstractEntity
    {
        if (!isset(self::$byId[$class]) || !array_key_exists($id, self::$byId[$class])) {
            self::$byId[$class][$id] = null;
            $data = db()->fetchOne("SELECT * FROM `" . $class::TABLE_NAME . "` WHERE id = " . $id);
            if ($data) {
                self::$byId[$class][$id] = new $class($class::mapFromDB($data));
            }
            Render::addLoadedTable($class::TABLE_NAME);
        }

        return self::$byId[$class][$id];
    }

    /**
     * Find a single entity from a SQL query string.
     *
     * @param string $class Class name
     * @param string $query The query
     *
     * @return ?AbstractEntity
     */
    public static function getOneByQuery(string $class, string $query): ?AbstractEntity
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        if (!isset(self::$oneBySql[$class]) || !array_key_exists($query, self::$oneBySql[$class])) {
            self::$oneBySql[$class][$query] = null;
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
     * Find multiple entities from a SQL query string.
     *
     * @param string $class Class name
     * @param string $query The query
     *
     * @return AbstractEntity[]
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
     * Remove an entity from the caches.
     *
     * @param string $class
     * @param int    $id
     *
     * @return void
     */
    public static function forget(string $class, int $id): void
    {
        unset(self::$byId[$class][$id]);
        self::$bySql[$class] = [];
        self::$oneBySql[$class] = [];
    }

    /**
     * Remove an entity from the caches.
     *
     * @param string $class
     * @param string $query The query
     *
     * @return void
     */
    public static function forgetByQuery(string $class, string $query): void
    {
        unset(self::$bySql[$class][$query]);
        unset(self::$oneBySql[$class][$query]);
    }

    /**
     * Remember an entity.
     *
     * @param string         $class
     * @param int            $id
     * @param AbstractEntity $entity
     *
     * @return void
     */
    public static function remember(string $class, int $id, AbstractEntity $entity): void
    {
        self::$byId[$class][$id] = $entity;
    }
}
