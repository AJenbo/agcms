<?php

namespace AGCMS;

use AGCMS\Interfaces\Renderable;

class VolatilePage implements Renderable
{
    /** @var string page title */
    private $title;

    /** @var string page link */
    private $link;

    /**
     * Set varables
     *
     * @param string $title
     * @param string $link
     */
    public function __construct(string $title, string $link)
    {
        $this->title = $title;
        $this->link = $link;
    }

    /**
     * Get page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get page link
     *
     * @return string
     */
    public function getCanonicalLink(): string
    {
        return $this->link;
    }
}
