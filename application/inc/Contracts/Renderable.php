<?php namespace App\Contracts;

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
