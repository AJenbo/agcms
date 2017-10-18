<?php namespace AGCMS\Entity;

use AGCMS\ORM;
use AGCMS\Render;

abstract class AbstractEntity implements InterfaceEntity
{
    /** @var ?int The entity ID. */
    protected $id;

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    abstract public function __construct(array $data);

    /**
     * Map data from DB table to entity.
     *
     * @param array The data from the database
     *
     * @return array
     */
    abstract public static function mapFromDB(array $data): array;

    /**
     * Set the entity ID.
     *
     * @param int|null The id
     *
     * @return self
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

        return $this->id;
    }

    /**
     * Get data in array format for the database.
     *
     * @return string[]
     */
    abstract protected function getDbArray(): array;

    /**
     * Save entity to database.
     */
    public function save(): InterfaceEntity
    {
        $data = $this->getDbArray();
        Render::addLoadedTable(static::TABLE_NAME);
        if (null === $this->id) {
            $this->insert($data);

            return $this;
        }

        $this->update($data);

        return $this;
    }

    /**
     * insert new entity in to the database.
     */
    private function insert(array $data): void
    {
        db()->query(
            '
            INSERT INTO `' . static::TABLE_NAME . '`
            (`' . implode('`,`', array_keys($data)) . '`)
            VALUES (' . implode(',', $data) . ')'
        );
        $this->setId(db()->insert_id);
        ORM::remember(static::class, db()->insert_id, $this);
    }

    /**
     * Update an entity in the database.
     */
    private function update(array $data): void
    {
        $sets = [];
        foreach ($data as $filedName => $value) {
            $sets[] = '`' . $filedName . '` = ' . $value;
        }
        db()->query('UPDATE `' . static::TABLE_NAME . '` SET ' . implode(',', $sets) . ' WHERE `id` = ' . $this->id);
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

        db()->query('DELETE FROM `' . static::TABLE_NAME . '` WHERE `id` = ' . $this->id);
        ORM::forget(static::class, $this->getId());

        return true;
    }
}
