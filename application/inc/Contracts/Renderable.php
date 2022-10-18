<?php

namespace App\Contracts;

interface Renderable
{
    /**
     * Get page title.
     */
    public function getTitle(): string;

    /**
     * Get page link.
     */
    public function getCanonicalLink(): string;
}
