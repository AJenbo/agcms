<?php namespace AGCMS;

use AGCMS\Interfaces\Renderable;

class VolatilePage implements Renderable
{
    /** @var string Page title */
    private $title;

    /** @var string Page link */
    private $link;

    /** @var Renderable[] Content list */
    private $list;

    /**
     * Set varables.
     *
     * @param string       $title
     * @param string       $link
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
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get page link.
     *
     * @return string
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
