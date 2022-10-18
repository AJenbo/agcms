<?php

namespace App\Models;

use App\Contracts\Entity;

interface InterfaceRichText extends Entity
{
    /**
     * Set the HTML body.
     *
     * @param string $html HTML body
     *
     * @return $this
     */
    public function setHtml(string $html): self;

    /**
     * Set the HTML body.
     */
    public function getHtml(): string;
}
