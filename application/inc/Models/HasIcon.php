<?php

namespace App\Models;

use App\Services\OrmService;

trait HasIcon
{
    /** @var ?int File id. */
    private ?int $iconId;

    /**
     * @return $this
     */
    public function setIcon(?File $icon): self
    {
        $this->iconId = $icon ? $icon->getId() : null;

        return $this;
    }

    public function getIcon(): ?File
    {
        $file = null;
        if (null !== $this->iconId) {
            $file = app(OrmService::class)->getOne(File::class, $this->iconId);
        }

        return $file;
    }
}
