<?php

class File
{
    const TABLE_NAME = 'files';

    // Backed by DB
    private $id;
    private $path;
    private $mime;
    private $size;
    private $description;
    private $width;
    private $height;
    private $aspect;

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

    public static function mapFromDB(array $data): array
    {
        return [
            'id'          => $data['id'] ?: null,
            'path'        => $data['path'] ?: '',
            'mime'        => $data['mime'] ?: '',
            'size'        => $data['size'] ?: 0,
            'description' => $data['alt'] ?: '',
            'width'       => $data['width'] ?: 0,
            'height'      => $data['height'] ?: 0,
            'aspect'      => $data['aspect'] ?: null,
        ];
    }

    // Getters and setters
    private function setId(int $id = null): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        if (!$this->id) {
            $this->save();
        }

        return $this->id;
    }

    public function setPath(string $path): self
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

    private function setSize(int $size): self
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

    private function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    private function setHeight(int $height): self
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
    public function save()
    {
        if (!$this->id) {
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
}
