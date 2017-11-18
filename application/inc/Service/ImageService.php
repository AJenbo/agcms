<?php namespace AGCMS\Service;

use AJenbo\Image;

class ImageService
{
    /** @var Image */
    private $image;
    /** @var int */
    private $cropX = 0;
    /** @var int */
    private $cropY = 0;
    /** @var int */
    private $cropW;
    /** @var int */
    private $cropH;
    /** @var bool */
    private $autoCrop = false;
    /** @var int */
    private $maxW;
    /** @var int */
    private $maxH;
    /** @var int */
    private $flip = 0;
    /** @var int */
    private $rotate = 0;

    /**
     * Load the image
     *
     * @param string $path
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
     * Set cropping operation
     *
     * @param int $startX
     * @param int $startY
     * @param int $width
     * @param int $height
     *
     * @return self
     */
    public function setCrop(int $startX, int $startY, int $width, int $height): self
    {
        $width = min($this->image->getWidth(), $width);
        $this->cropW = max(0, $width) ?: $this->image->getWidth();

        $height = min($this->image->getHeight(), $height);
        $this->cropH = max(0, $height) ?: $this->image->getHeight();

        $startX = $startX + $this->cropW < $this->image->getWidth() ? $startX : 0;
        $this->cropX = max(0, $startX);

        $startY = $startY + $this->cropH < $this->image->getHeight() ? $startY : 0;
        $this->cropY = max(0, $startY);

        return $this;
    }

    /**
     * Set autocrop
     *
     * @param bool $autoCrop
     *
     * @return self
     */
    public function setAutoCrop(bool $autoCrop): self
    {
        $this->autoCrop = $autoCrop;

        return $this;
    }

    /**
     * Set scale operation
     *
     * @param int $width
     * @param int $maxH
     *
     * @return self
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
     * Set flip/mirror
     *
     * @param int $flip
     *
     * @return self
     */
    public function setFlip(int $flip): self
    {
        $this->flip = $flip;

        return $this;
    }

    /**
     * Set rotate operation
     *
     * @param int $rotate
     *
     * @return self
     */
    public function setRotate(int $rotate): self
    {
        $this->rotate = $rotate;

        return $this;
    }


    /**
     * Test if the settings will cause a change in the image
     *
     * @return bool
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
     * Get image width
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->image->getWidth();
    }

    /**
     * Get image height
     *
     * @return int
     */
    public function getHeight(): int
    {
        return $this->image->getHeight();
    }

    /**
     * Performe the set operations and save the image
     *
     * Doing so will reset the changes
     *
     * @param string $targetPath
     * @param string $type
     *
     * @return void
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
     *
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
     * Reset operations to loaded image
     *
     * @return void
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
