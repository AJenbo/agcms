<?php namespace AGCMS\Entity;

interface InterfaceEntity
{
    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data = []);

    /**
     * Map data from DB table to entity.
     *
     * @param array $data The data from the database
     *
     * @return array
     */
    public static function mapFromDB(array $data): array;

    /**
     * Get the entity ID.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Save entity to database.
     *
     * @return $this
     */
    public function save(): self;

    /**
     * Delete entity.
     */
    public function delete(): bool;
}
