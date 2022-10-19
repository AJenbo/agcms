<?php

namespace App\Services;

use AJenbo\Image;

class ImageService
{
    private Image $image;
    private int $cropX = 0;
    private int $cropY = 0;
    private int $cropW;
    private int $cropH;
    private bool $autoCrop = false;
    private int $maxW;
    private int $maxH;
    private int $flip = 0;
    private int $rotate = 0;

    /**
     * Load the image.
     */
    public function __construct(string $path)
    {
        $this->image = new Image($path);
        $this->cropW = $this->image->getWidth();
        $this->cropH = $this->image->getHeight();
        $this->maxW = $this->image->getWidth();
        $this->maxH = $this->image->getHeight();
    }

    /**
     * Set cropping operation.
     *
     * @return $this
     */
    public function setCrop(int $startX, int $startY, int $width, int $height): self
    {
        $width = min($this->image->getWidth(), $width);
        $this->cropW = max(0, $width) ?: $this->image->getWidth();

        $height = min($this->image->getHeight(), $height);
        $this->cropH = max(0, $height) ?: $this->image->getHeight();

        $startX = $startX + $this->cropW <= $this->image->getWidth() ? $startX : 0;
        $this->cropX = max(0, $startX);

        $startY = $startY + $this->cropH <= $this->image->getHeight() ? $startY : 0;
        $this->cropY = max(0, $startY);

        return $this;
    }

    /**
     * Set autocrop.
     *
     * @return $this
     */
    public function setAutoCrop(bool $autoCrop): self
    {
        $this->autoCrop = $autoCrop;

        return $this;
    }

    /**
     * Set scale operation.
     *
     * @return $this
     */
    public function setScale(int $width, int $height = 0): self
    {
        $width = min($width, $this->image->getWidth());
        $this->maxW = max(0, $width) ?: $this->image->getWidth();

        $height = min($height, $this->image->getHeight());
        $this->maxH = max(0, $height) ?: $this->image->getHeight();

        return $this;
    }

    /**
     * Set flip/mirror.
     *
     * @return $this
     */
    public function setFlip(int $flip): self
    {
        $this->flip = $flip;

        return $this;
    }

    /**
     * Set rotate operation.
     *
     * @return $this
     */
    public function setRotate(int $rotate): self
    {
        $this->rotate = $rotate;

        return $this;
    }

    /**
     * Test if the settings will cause a change in the image.
     */
    public function isNoOp(): bool
    {
        if ($this->cropW !== $this->image->getWidth() || $this->cropH !== $this->image->getHeight()) {
            return false;
        }

        if ($this->autoCrop) {
            $content = $this->image->findContent();
            if ($content['width'] !== $this->image->getWidth() || $content['height'] !== $this->image->getHeight()) {
                return false;
            }
        }

        if ($this->maxW !== $this->image->getWidth() || $this->maxH !== $this->image->getHeight()) {
            return false;
        }

        if ($this->flip || $this->rotate) {
            return false;
        }

        return true;
    }

    /**
     * Get image width.
     */
    public function getWidth(): int
    {
        return $this->image->getWidth();
    }

    /**
     * Get image height.
     */
    public function getHeight(): int
    {
        return $this->image->getHeight();
    }

    /**
     * Performe the set operations and save the image.
     *
     * Doing so will reset the changes
     */
    public function processImage(string $targetPath, string $type = 'jpeg'): void
    {
        $this->image->crop($this->cropX, $this->cropY, $this->cropW, $this->cropH);

        if ($this->autoCrop) {
            $this->autoCrop();
        }

        $this->image->resize($this->maxW, $this->maxH);

        if ($this->flip) {
            // Flip / mirror
            $this->image->flip(1 === $this->flip ? 'x' : 'y');
        }

        $this->image->rotate($this->rotate);

        $this->image->save($targetPath, $type);
        $this->reset();
    }

    /**
     * Trim image whitespace.
     */
    private function autoCrop(): void
    {
        $imageContent = $this->image->findContent();

        $this->maxW = min($this->maxW, $imageContent['width']);
        $this->maxH = min($this->maxH, $imageContent['height']);

        $this->image->crop(
            $imageContent['x'],
            $imageContent['y'],
            $imageContent['width'],
            $imageContent['height']
        );
    }

    /**
     * Reset operations to loaded image.
     */
    private function reset(): void
    {
        $this->cropX = 0;
        $this->cropY = 0;
        $this->cropW = $this->image->getWidth();
        $this->cropH = $this->image->getHeight();

        $this->autoCrop = false;

        $this->maxW = $this->image->getWidth();
        $this->maxH = $this->image->getHeight();

        $this->flip = 0;
        $this->rotate = 0;
    }
}
