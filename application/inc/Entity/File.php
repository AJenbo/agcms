<?php namespace AGCMS\Entity;

use AGCMS\ORM;
use Exception;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

class File extends AbstractEntity
{
    /** Table name in database. */
    const TABLE_NAME = 'files';

    // Backed by DB
    /** @var string File path. */
    private $path;

    /** @var string File mime. */
    private $mime;

    /** @var int File byte size. */
    private $size;

    /** @var string Text description of file. */
    private $description = '';

    /** @var int Object width in px. */
    private $width = 0;

    /** @var int Object height in px. */
    private $height = 0;

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setPath($data['path'])
            ->setMime($data['mime'])
            ->setSize($data['size'])
            ->setDescription($data['description'])
            ->setWidth($data['width'])
            ->setHeight($data['height'])
            ->setId($data['id'] ?? null);
    }

    /**
     * Map data from DB table to entity.
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
        ];
    }

    // Getters and setters

    /**
     * Set path.
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
     * Return the file path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the mime type.
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
     * Get the mime type.
     *
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * Set the file size.
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
     * Get the file size.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Set the file text description.
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
     * Get the text description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set display width.
     *
     * @param int $width
     *
     * @return self
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get display width.
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Set display height.
     *
     * @param int $height
     *
     * @return self
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get display height.
     *
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    // ORM related functions

    /**
     * Get data in array format for the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
    {
        return [
            'path'   => db()->eandq($this->path),
            'mime'   => db()->eandq($this->mime),
            'size'   => (string) $this->size,
            'alt'    => db()->eandq($this->description),
            'width'  => (string) $this->width,
            'height' => (string) $this->height,
        ];
    }

    /**
     * Rename file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function move(string $path): bool
    {
        //Rename/move or give an error
        if (!rename(_ROOT_ . $this->getPath(), _ROOT_ . $path)) {
            return false;
        }

        $this->replacePaths($this->getPath(), $path);
        $this->setPath($path)->save();

        return true;
    }

    /**
     * Update related data.
     *
     * @param string $path
     * @param string $newPath
     *
     * @return void
     */
    private function replacePaths(string $path, string $newPath): void
    {
        $newPathEsc = db()->esc($newPath);
        $pathEsc = db()->esc($path);
        db()->query("UPDATE sider    SET text = REPLACE(text, '=\"" . $pathEsc . "\"', '=\"" . $newPathEsc . "\"')");
        db()->query("UPDATE template SET text = REPLACE(text, '=\"" . $pathEsc . "\"', '=\"" . $newPathEsc . "\"')");
        db()->query("UPDATE special  SET text = REPLACE(text, '=\"" . $pathEsc . "\"', '=\"" . $newPathEsc . "\"')");
        db()->query("UPDATE krav     SET text = REPLACE(text, '=\"" . $pathEsc . "\"', '=\"" . $newPathEsc . "\"')");
    }

    /**
     * Check if file is in use.
     *
     * @param bool $onlyCheckHtml
     *
     * @return bool
     */
    public function isInUse(bool $onlyCheckHtml = false): bool
    {
        $escapedPath = db()->esc($this->path);

        if ($onlyCheckHtml) {
            return (bool) db()->fetchOne(
                "
                (SELECT id FROM `sider` WHERE `text` LIKE '%=\"$escapedPath\"%' LIMIT 1)
                UNION (SELECT id FROM `template` WHERE `text` LIKE '%=\"$escapedPath\"%' LIMIT 1)
                UNION (SELECT id FROM `special` WHERE `text` LIKE '%=\"$escapedPath\"%' LIMIT 1)
                UNION (SELECT id FROM `krav`    WHERE `text` LIKE '%=\"$escapedPath\"%' LIMIT 1)
                "
            );
        }

        return (bool) db()->fetchOne(
            '
            (
                SELECT id FROM `sider`
                WHERE `icon_id` = ' . $this->getId() . " OR `text` LIKE '%=\"$escapedPath\"%' LIMIT 1
            )
            UNION (
                SELECT id FROM `template`
                WHERE `icon_id` = " . $this->getId() . " OR `text` LIKE '%=\"$escapedPath\"%' LIMIT 1
            )
            UNION (SELECT id FROM `special` WHERE `text` LIKE '%=\"$escapedPath\"%' LIMIT 1)
            UNION (SELECT id FROM `krav`    WHERE `text` LIKE '%=\"$escapedPath\"%' LIMIT 1)
            UNION (SELECT id FROM `maerke`  WHERE `icon_id`  = " . $this->getId() . ' LIMIT 1)
            UNION (SELECT id FROM `kat`     WHERE `icon_id` = ' . $this->getId() . ' LIMIT 1)
            '
        );
    }

    /**
     * Create new File from a file path.
     *
     * @param string $path The file path
     *
     * @return self
     */
    public static function fromPath(string $path): self
    {
        $imagesize = @getimagesize(_ROOT_ . $path);

        $guesser = MimeTypeGuesser::getInstance();
        $mime = $guesser->guess(_ROOT_ . $path);

        $file = new self([
            'path'        => $path,
            'mime'        => $mime,
            'size'        => filesize(_ROOT_ . $path),
            'description' => '',
            'width'       => $imagesize[0] ?? 0,
            'height'      => $imagesize[1] ?? 0,
        ]);

        return $file;
    }

    /**
     * Delete entity and file.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete(): bool
    {
        if ($this->isInUse()) {
            throw new Exception(sprintf(_('"%s" is still in use.'), $this->path));
        }

        if (file_exists(_ROOT_ . $this->path) && !unlink(_ROOT_ . $this->path)) {
            throw new Exception(sprintf(_('Could not delete "%s".'), $this->path));
        }

        return parent::delete();
    }

    /**
     * Find entity by file path.
     *
     * @param string $path The file path
     *
     * @return ?self
     */
    public static function getByPath(string $path): ?self
    {
        return ORM::getOneByQuery(
            self::class,
            'SELECT * FROM `' . self::TABLE_NAME . "` WHERE path = '" . db()->esc($path) . "'"
        );
    }
}
