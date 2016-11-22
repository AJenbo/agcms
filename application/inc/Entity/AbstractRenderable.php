<?php

abstract class AbstractRenderable extends AbstractEntity
{
    /**
     * The title
     */
    protected $title;

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
}
