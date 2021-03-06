<?php namespace App\Models;

use App\Contracts\Entity;
use App\Services\DbService;
use App\Services\OrmService;

abstract class AbstractEntity implements Entity
{
    /** Table name in database. */
    const TABLE_NAME = '';

    /** @var ?int The entity ID. */
    protected $id;

    /**
     * Clone entity.
     */
    public function __clone()
    {
        $this->id = null;
    }

    /**
     * Set the entity ID.
     *
     * @param int|null $id The id
     *
     * @return $this
     */
    protected function setId(int $id = null): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        if (null === $this->id) {
            $this->save();
        }

        return (int) $this->id;
    }

    /**
     * Get data in array format for the database.
     *
     * @return array<string, string>
     */
    abstract protected function getDbArray(): array;

    /**
     * Save entity to database.
     *
     * @return $this
     */
    public function save(): Entity
    {
        /** @var DbService */
        $db = app(DbService::class);

        $data = $this->getDbArray();
        $db->addLoadedTable(static::TABLE_NAME);
        if (null === $this->id) {
            $this->insert($data);

            return $this;
        }

        $this->update($data);

        return $this;
    }

    /**
     * insert new entity in to the database.
     *
     * @param array<string, string> $data
     *
     * @return void
     */
    private function insert(array $data): void
    {
        /** @var DbService */
        $db = app(DbService::class);

        /** @var OrmService */
        $orm = app(OrmService::class);

        $id = $db->query(
            '
            INSERT INTO `' . static::TABLE_NAME . '`
            (`' . implode('`,`', array_keys($data)) . '`)
            VALUES (' . implode(',', $data) . ')'
        );
        $this->setId($id);
        $orm->remember(static::class, $id, $this);
    }

    /**
     * Update an entity in the database.
     *
     * @param array<string, string> $data
     *
     * @return void
     */
    private function update(array $data): void
    {
        /** @var DbService */
        $db = app(DbService::class);

        $sets = [];
        foreach ($data as $filedName => $value) {
            $sets[] = '`' . $filedName . '` = ' . $value;
        }
        $db->query(
            'UPDATE `' . static::TABLE_NAME . '` SET ' . implode(',', $sets) . ' WHERE `id` = ' . $this->id
        );
    }

    public function delete(): bool
    {
        if (null === $this->id) {
            return true;
        }

        /** @var DbService */
        $db = app(DbService::class);

        /** @var OrmService */
        $orm = app(OrmService::class);

        $db->query('DELETE FROM `' . static::TABLE_NAME . '` WHERE `id` = ' . $this->id);
        $orm->forget(static::class, $this->getId());

        return true;
    }
}
