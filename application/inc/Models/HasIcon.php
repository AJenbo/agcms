<?php namespace App\Models;

use App\Services\OrmService;

trait HasIcon
{
    /** @var ?int File id. */
    private $iconId;

    /**
     * Set icon.
     *
     * @param ?File $icon
     *
     * @return $this
     */
    public function setIcon(?File $icon): self
    {
        $this->iconId = $icon ? $icon->getId() : null;

        return $this;
    }

    /**
     * Get the file that is used as an icon.
     *
     * @return ?File
     */
    public function getIcon(): ?File
    {
        $file = null;
        if (null !== $this->iconId) {
            /** @var OrmService */
            $orm = app(OrmService::class);

            /** @var ?File */
            $file = $orm->getOne(File::class, $this->iconId);
        }

        return $file;
    }
}
