<?php namespace App\Services;

use App\Exceptions\Exception;
use App\Models\AbstractEntity;

class OrmService
{
    /** @var array<string, array<int, ?AbstractEntity>> Cache entity by id. */
    private $byId = [];

    /** @var array<string, array<string, array<int, AbstractEntity>>> Cache multiple entity by query. */
    private $bySql = [];

    /** @var array<string, array<string, ?AbstractEntity>> Cache entity by query. */
    private $oneBySql = [];

    /**
     * Get a single entitly by id.
     *
     * @param string $class Class name
     * @param int    $id    Id of the entity
     *
     * @return ?AbstractEntity
     */
    public function getOne(string $class, int $id): ?AbstractEntity
    {
        if (!isset($this->byId[$class]) || !array_key_exists($id, $this->byId[$class])) {
            /** @var DbService */
            $db = app(DbService::class);
            $data = $db->fetchOne('SELECT * FROM `' . $class::TABLE_NAME . '` WHERE id = ' . $id);
            $db->addLoadedTable($class::TABLE_NAME);
            $this->byId[$class][$id] = $data ? new $class($class::mapFromDB($data)) : null;
        }

        return $this->byId[$class][$id];
    }

    /**
     * Find a single entity from a SQL query string.
     *
     * @param string $class Class name
     * @param string $query The query
     *
     * @return ?AbstractEntity
     */
    public function getOneByQuery(string $class, string $query): ?AbstractEntity
    {
        $query = preg_replace('/\s+/u', ' ', $query);
        if (null === $query) {
            throw new Exception('preg_replace failed');
        }
        $query = trim($query);

        if (!isset($this->oneBySql[$class]) || !array_key_exists($query, $this->oneBySql[$class])) {
            $this->oneBySql[$class][$query] = null;

            /** @var DbService */
            $db = app(DbService::class);
            $data = $db->fetchOne($query);
            $db->addLoadedTable($class::TABLE_NAME);
            if ($data) {
                if (!isset($this->byId[$class][$data['id']])) {
                    $this->byId[$class][$data['id']] = new $class($class::mapFromDB($data));
                }
                $this->oneBySql[$class][$query] = $this->byId[$class][$data['id']];
            }
        }

        return $this->oneBySql[$class][$query];
    }

    /**
     * Find multiple entities from a SQL query string.
     *
     * @param string $class Class name
     * @param string $query The query
     *
     * @return AbstractEntity[]
     */
    public function getByQuery(string $class, string $query): array
    {
        $query = preg_replace('/\s+/u', ' ', $query);
        if (null === $query) {
            throw new Exception('preg_replace failed');
        }
        $query = trim($query);
        if (!isset($this->bySql[$class][$query])) {
            $this->bySql[$class][$query] = [];
            /** @var DbService */
            $db = app(DbService::class);
            foreach ($db->fetchArray($query) as $data) {
                if (!isset($this->byId[$class][$data['id']])) {
                    $this->byId[$class][$data['id']] = new $class($class::mapFromDB($data));
                }
                $this->bySql[$class][$query][] = $this->byId[$class][$data['id']];
            }
            $db->addLoadedTable($class::TABLE_NAME);
        }

        return $this->bySql[$class][$query];
    }

    /**
     * Remove an entity from the caches.
     *
     * @param string $class
     * @param int    $id
     */
    public function forget(string $class, int $id): void
    {
        unset($this->byId[$class][$id]);
        $this->bySql[$class] = [];
        $this->oneBySql[$class] = [];
    }

    /**
     * Remove an entity from the caches.
     *
     * @param string $class
     * @param string $query The query
     */
    public function forgetByQuery(string $class, string $query): void
    {
        unset($this->bySql[$class][$query], $this->oneBySql[$class][$query]);
    }

    /**
     * Remember an entity.
     *
     * @param string         $class
     * @param int            $id
     * @param AbstractEntity $entity
     */
    public function remember(string $class, int $id, AbstractEntity $entity): void
    {
        $this->byId[$class][$id] = $entity;
    }
}
