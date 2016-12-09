<?php

class File extends AbstractEntity
{
    /**
     * Table name in database
     */
    const TABLE_NAME = 'files';

    // Backed by DB
    /**
     * File path
     */
    private $path;

    /**
     * File mime
     */
    private $mime;

    /**
     * File byte size
     */
    private $size;

    /**
     * Text description of file
     */
    private $description;

    /**
     * Object width in px
     */
    private $width;

    /**
     * Object height in px
     */
    private $height;

    /**
     * Video aspect (4-3, 16-9)
     */
    private $aspect;

    /**
     * Construct the entity
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setPath($data['path'])
            ->setMime($data['mime'])
            ->setSize($data['size'])
            ->setDescription($data['description'])
            ->setWidth($data['width'])
            ->setHeight($data['height'])
            ->setAspect($data['aspect']);
    }

    /**
     * Map data from DB table to entity
     *
     * @param array The data from the database
     *
     * @return array
     */
    public static function mapFromDB(array $data): array
    {
        return [
            'id'          => $data['id'],
            'path'        => $data['path'],
            'mime'        => $data['mime'],
            'size'        => $data['size'],
            'description' => $data['alt'],
            'width'       => $data['width'],
            'height'      => $data['height'],
            'aspect'      => $data['aspect'] ?: null,
        ];
    }

    // Getters and setters
    /**
     * Set path
     *
     * @param string $path The file path
     *
     * @return self
     */
    private function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Return the file path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the mime type
     *
     * @param string $mime The mime type
     *
     * @return self
     */
    public function setMime(string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    /**
     * Get the mime type
     *
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * Set the file size
     *
     * @param int $size The file size in bytes
     *
     * @return self
     */
    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get the file size
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Set the file text description
     *
     * @param string $description Text description
     *
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the text description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set width
     *
     * @param int $width The object width
     *
     * @return self
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param int $width The object height
     *
     * @return self
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Set video aspect
     *
     * @param string $aspect In the format of 16-9
     *
     * @return self
     */
    public function setAspect(string $aspect = null): self
    {
        $this->aspect = $aspect;

        return $this;
    }

    /**
     * Get the asspect
     *
     * @return ?string
     */
    public function getAspect()
    {
        return $this->aspect;
    }

    // ORM related functions
    /**
     * Save entity to database
     */
    public function save()
    {
        if ($this->id === null) {
            db()->query(
                "
                INSERT INTO `" . self::TABLE_NAME . "` (
                    `path`,
                    `mime`,
                    `size`,
                    `alt`,
                    `width`,
                    `height`,
                    `aspect`
                ) VALUES (
                    '" . db()->esc($this->path) . "',
                    '" . db()->esc($this->mime) . "',
                    " . $this->size . ",
                    '" . db()->esc($this->description) . "',
                    " . $this->width . ",
                    " . $this->height . ",
                    " . ($this->aspect ? ("'" . db()->esc($this->aspect) . "'") : "NULL") . "
                )"
            );
            $this->setId(db()->insert_id);
        } else {
            db()->query(
                "
                UPDATE `" . self::TABLE_NAME ."` SET
                    `path` = '" . db()->esc($this->path) . "',
                    `mime` = '" . db()->esc($this->mime) . "',
                    `size` = " . $this->size . ",
                    `alt` = '" . db()->esc($this->description) . "',
                    `width` = " . $this->width . ",
                    `height` = " . $this->height . ",
                    `aspect` = " . ($this->aspect ? ("'" . db()->esc($this->aspect) . "'") : "NULL") . "
                WHERE `id` = " . $this->id
            );
        }
        Render::addLoadedTable(self::TABLE_NAME);
    }

    /**
     * Create new File from a file path
     *
     * @param string $path The file path
     *
     * @return self
     */
    public static function fromPath(string $path): self
    {
        $imagesize = @getimagesize(_ROOT_ . $path);

        $file = new self([
            'path' => $path,
            'mime' => get_mime_type(_ROOT_ . $path),
            'size' => filesize(_ROOT_ . $path),
            'description' => '',
            'width' => $imagesize[0] ?? 0,
            'height' => $imagesize[1] ?? 0,
            'aspect' => '',
        ]);

        return $file;
    }

    /**
     * Delete entity and file
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (@unlink(_ROOT_ . $this->path)) {
            db()->query("DELETE FROM `" . self::TABLE_NAME . "` WHERE `id` = " . $this->id);
            ORM::forget(self::class, $this->id);
            return true;
        }

        return false;
    }

    /**
     * Find entity by file path
     *
     * @param string $path The file path
     *
     * @return ?self
     */
    public static function getByPath(string $path)
    {
        return ORM::getOneByQuery(
            File::class,
            "SELECT * FROM `" . self::TABLE_NAME . "` WHERE path = '" . db()->esc($path) . "'"
        );
    }
}
