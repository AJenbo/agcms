<?php

namespace App\Models;

use App\Contracts\Renderable;

abstract class AbstractRenderable extends AbstractEntity implements Renderable
{
    /** @var string The title. */
    protected $title = '';

    /**
     * Set the title.
     *
     * @param string $title The title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the url slug.
     */
    abstract public function getSlug(): string;

    /**
     * Get canonical url for this entity.
     */
    public function getCanonicalLink(): string
    {
        return '/' . $this->getSlug();
    }
}
