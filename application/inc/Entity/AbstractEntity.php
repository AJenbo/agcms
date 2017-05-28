<?php namespace AGCMS\Entity;

use AGCMS\ORM;

abstract class AbstractEntity implements InterfaceEntity
{
    /**
     * The entity ID
     */
    protected $id;

    /**
     * Construct the entity
     *
     * @param array $data The entity data
     */
    abstract public function __construct(array $data);

    /**
     * Map data from DB table to entity
     *
     * @param array The data from the database
     *
     * @return array
     */
    abstract public static function mapFromDB(array $data): array;

    /**
     * Set the entity ID
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
     * Get the entity ID
     *
     * @return int
     */
    public function getId(): int
    {
        if ($this->id === null) {
            $this->save();
        }

        return $this->id;
    }

    /**
     * Save entity to database
     */
    abstract public function save(): InterfaceEntity;

    /**
     * Delete entity
     *
     * @return bool
     */
    public function delete(): bool
    {
        db()->query("DELETE FROM `" . self::TABLE_NAME . "` WHERE `id` = " . $this->id);
        ORM::forget(self::class, $this->id);
        return true;
    }
}
