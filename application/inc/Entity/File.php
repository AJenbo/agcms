<?php

class File extends AbstractEntity
{
    const TABLE_NAME = 'files';

    // Backed by DB
    private $path;
    private $mime;
    private $size;
    private $description;
    private $width;
    private $height;
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
    private function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setMime(string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setAspect(string $aspect = null): self
    {
        $this->aspect = $aspect;

        return $this;
    }

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
                    '" . $this->size . ",
                    '" . db()->esc($this->description) . "',
                    '" . $this->width . ",
                    '" . $this->height . ",
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

    public static function fromPath(string $path)
    {
        $imagesize = @getimagesize(_ROOT_ . $path);

        $file = new self();
        $file->setPath($path)
            ->setMime(get_mime_type($path))
            ->setSize(filesize(_ROOT_ . $path))
            ->setWidth($imagesize[0] ?? 0)
            ->setHeight($imagesize[1] ?? 0);

        return $file;
    }

    public function delete(): bool
    {
        if (@unlink(_ROOT_ . $this->path)) {
            db()->query("DELETE FROM `" . self::TABLE_NAME . "` WHERE `id` = " . $this->id);
            ORM::forget(self::class, $this->id);
            return true;
        }

        return false;
    }

    public static function getByPath(string $path)
    {
        return ORM::getOneByQuery(
            File::class,
            "SELECT * FROM `" . self::TABLE_NAME . "` WHERE path = '" . db()->esc($path) . "'"
        );
    }
}
