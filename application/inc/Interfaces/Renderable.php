<?php namespace AGCMS\Interfaces;

interface Renderable
{
    /**
     * Get page title.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Get page link.
     *
     * @return string
     */
    public function getCanonicalLink(): string;
}
