<?php namespace App\Contracts;

interface Entity
{
    /**
     * Construct the entity.
     *
     * @param array<string, mixed> $data The entity data
     */
    public function __construct(array $data = []);

    /**
     * Map data from DB table to entity.
     *
     * @param array<string|int, string> $data The data from the database
     *
     * @return array<string, mixed>
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
