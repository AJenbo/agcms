<?php

class AbstractRenderable extends AbstractEntity
{
    /**
     * The title
     */
    protected $title;

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
     * Set the title
     *
     * @param string $title The title
     *
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the url slug
     *
     * @return string
     */
    abstract public function getSlug(): string;

    /**
     * Get canonical url for this entity
     *
     * @return string
     */
    public function getCanonicalLink(): string
    {
        return '/' . $this->getSlug();
    }

    /**
     * Save entity to database
     */
    abstract public function save();
}
