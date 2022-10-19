<?php

namespace App\Models;

use App\Contracts\Renderable;

class VolatilePage implements Renderable
{
    /** @var string Page title */
    private string $title;

    /** @var string Page link */
    private string $link;

    /** @var Renderable[] Content list */
    private array $list;

    /**
     * Set varables.
     *
     * @param Renderable[] $list
     */
    public function __construct(string $title, string $link, array $list = [])
    {
        $this->title = $title;
        $this->link = $link;
        $this->list = $list;
    }

    /**
     * Get page title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get page link.
     */
    public function getCanonicalLink(): string
    {
        return $this->link;
    }

    /**
     * Get listed pages.
     *
     * @return Renderable[]
     */
    public function getPages(): array
    {
        return $this->list;
    }
}
