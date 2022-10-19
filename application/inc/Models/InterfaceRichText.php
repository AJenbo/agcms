<?php

namespace App\Models;

use App\Contracts\Entity;

interface InterfaceRichText extends Entity
{
    /**
     * @return $this
     */
    public function setHtml(string $html): self;

    public function getHtml(): string;
}
