<?php

namespace App\Models;

use App\Exceptions\Exception;
use App\Exceptions\InvalidInput;
use App\Services\DbService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

class File extends AbstractEntity
{
    /** Table name in database. */
    public const TABLE_NAME = 'files';

    // Backed by DB

    /** @var string File path. */
    private string $path;

    /** @var string File mime. */
    private string $mime;

    /** @var int File byte size. */
    private int $size;

    /** @var string Text description of file. */
    private string $description = '';

    /** @var int Object width in px. */
    private int $width = 0;

    /** @var int Object height in px. */
    private int $height = 0;

    public function __construct(array $data = [])
    {
        $this->setPath($data['path'])
            ->setMime($data['mime'])
            ->setSize($data['size'])
            ->setDescription($data['description'])
            ->setWidth($data['width'])
            ->setHeight($data['height'])
            ->setId($data['id'] ?? null);
    }

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
     * @return $this
     */
    private function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return $this
     */
    public function setMime(string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * @param int $size The file size in bytes
     *
     * @return $this
     */
    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get the file size.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the text description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set display width.
     *
     * @return $this
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get display width.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Set display height.
     *
     * @return $this
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get display height.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $db = app(DbService::class);

        return [
            'path'   => $db->quote($this->path),
            'mime'   => $db->quote($this->mime),
            'size'   => (string)$this->size,
            'alt'    => $db->quote($this->description),
            'width'  => (string)$this->width,
            'height' => (string)$this->height,
        ];
    }

    /**
     * Rename file.
     */
    public function move(string $path): bool
    {
        $app = app();

        //Rename/move or give an error
        if (!rename($app->basePath($this->getPath()), $app->basePath($path))) {
            return false;
        }

        $this->replacePaths($this->getPath(), $path);
        $this->setPath($path)->save();

        return true;
    }

    /**
     * Update related data.
     */
    private function replacePaths(string $path, string $newPath): void
    {
        $db = app(DbService::class);

        $newPathEsc = $db->quote('="' . $newPath . '"');
        $pathEsc = $db->quote('="' . $path . '"');
        $db->query('UPDATE sider     SET text = REPLACE(text, ' . $pathEsc . ', ' . $newPathEsc . ')');
        $db->query('UPDATE template  SET text = REPLACE(text, ' . $pathEsc . ', ' . $newPathEsc . ')');
        $db->query('UPDATE special   SET text = REPLACE(text, ' . $pathEsc . ', ' . $newPathEsc . ')');
        $db->query('UPDATE krav      SET text = REPLACE(text, ' . $pathEsc . ', ' . $newPathEsc . ')');
        $db->query('UPDATE newsmails SET text = REPLACE(text, ' . $pathEsc . ', ' . $newPathEsc . ')');
    }

    /**
     * Check if file is in use.
     */
    public function isInUse(bool $onlyCheckHtml = false): bool
    {
        $db = app(DbService::class);

        $escapedPath = $db->quote('%="' . $this->path . '"%');

        $sql = "
              (SELECT id FROM `sider`     WHERE `text` LIKE $escapedPath LIMIT 1)
        UNION (SELECT id FROM `template`  WHERE `text` LIKE $escapedPath LIMIT 1)
        UNION (SELECT id FROM `special`   WHERE `text` LIKE $escapedPath LIMIT 1)
        UNION (SELECT id FROM `krav`      WHERE `text` LIKE $escapedPath LIMIT 1)
        UNION (SELECT id FROM `newsmails` WHERE `text` LIKE $escapedPath LIMIT 1)
        ";
        $db->addLoadedTable('sider', 'template', 'special', 'krav', 'newsmails');

        if (!$onlyCheckHtml) {
            $sql .= '
            UNION (SELECT id FROM `sider`    WHERE `icon_id` = ' . $this->getId() . ' LIMIT 1)
            UNION (SELECT id FROM `template` WHERE `icon_id` = ' . $this->getId() . ' LIMIT 1)
            UNION (SELECT id FROM `maerke`   WHERE `icon_id` = ' . $this->getId() . ' LIMIT 1)
            UNION (SELECT id FROM `kat`      WHERE `icon_id` = ' . $this->getId() . ' LIMIT 1)
            ';
            $db->addLoadedTable('kat');
        }

        return (bool)$db->fetchOne($sql);
    }

    /**
     * Create new File from a file path.
     *
     * @todo Load size of video
     *
     * @return static
     */
    public static function fromPath(string $path): self
    {
        $fullPath = app()->basePath($path);
        $imagesize = @getimagesize($fullPath);
        if (!$imagesize) {
            $imagesize = [];
        }

        $guesser = MimeTypeGuesser::getInstance();
        $mime = $guesser->guess($fullPath);

        $file = new static([
            'path'        => $path,
            'mime'        => $mime,
            'size'        => filesize($fullPath),
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
     * @throws InvalidInput
     */
    public function delete(): bool
    {
        if ($this->isInUse()) {
            throw new InvalidInput(sprintf(_('"%s" is still in use.'), $this->path), 423);
        }

        $app = app();

        if (file_exists($app->basePath($this->path)) && !unlink($app->basePath($this->path))) {
            throw new Exception(sprintf(_('Could not delete "%s".'), $this->path), 403);
        }

        return parent::delete();
    }

    /**
     * @return ?self
     */
    public static function getByPath(string $path): ?self
    {
        $file = app(OrmService::class)->getOneByQuery(
            static::class,
            'SELECT * FROM `' . self::TABLE_NAME . '` WHERE path = ' . app(DbService::class)->quote($path)
        );

        return $file;
    }
}
