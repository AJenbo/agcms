<?php namespace AGCMS\Entity;

use AGCMS\ORM;

abstract class AbstractEntity implements InterfaceEntity
{
    /** Table name in database. */
    const TABLE_NAME = '';

    /** @var ?int The entity ID. */
    protected $id;

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    abstract public function __construct(array $data = []);

    /**
     * Clone entity.
     */
    public function __clone()
    {
        $this->id = null;
    }

    /**
     * Map data from DB table to entity.
     *
     * @param array $data The data from the database
     *
     * @return array
     */
    abstract public static function mapFromDB(array $data): array;

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

    /**
     * Get the entity ID.
     *
     * @return int
     */
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
     * @return string[]
     */
    abstract protected function getDbArray(): array;

    /**
     * Save entity to database.
     *
     * @return $this
     */
    public function save(): InterfaceEntity
    {
        $data = $this->getDbArray();
        app('db')->addLoadedTable(static::TABLE_NAME);
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
     * @param array $data
     *
     * @return void
     */
    private function insert(array $data): void
    {
        $id = app('db')->query(
            '
            INSERT INTO `' . static::TABLE_NAME . '`
            (`' . implode('`,`', array_keys($data)) . '`)
            VALUES (' . implode(',', $data) . ')'
        );
        $this->setId($id);
        app('orm')->remember(static::class, $id, $this);
    }

    /**
     * Update an entity in the database.
     *
     * @param array $data
     *
     * @return void
     */
    private function update(array $data): void
    {
        $sets = [];
        foreach ($data as $filedName => $value) {
            $sets[] = '`' . $filedName . '` = ' . $value;
        }
        app('db')->query(
            'UPDATE `' . static::TABLE_NAME . '` SET ' . implode(',', $sets) . ' WHERE `id` = ' . $this->id
        );
    }

    /**
     * Delete entity.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (null === $this->id) {
            return true;
        }

        app('db')->query('DELETE FROM `' . static::TABLE_NAME . '` WHERE `id` = ' . $this->id);
        app('orm')->forget(static::class, $this->getId());

        return true;
    }
}
