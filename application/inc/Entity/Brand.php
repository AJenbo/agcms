<?php

class Brand
{
    const TABLE_NAME = 'maerke';

    // Backed by DB
    private $id;
    private $title;
    private $link;
    private $iconPath;

    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTitle($data['title'])
            ->setLink($data['link'])
            ->setIconPath($data['icon_path']);
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'        => $data['id'] ?: null,
            'title'     => $data['navn'] ?: '',
            'link'      => $data['link'] ?: '',
            'icon_path' => $data['ico'] ?: '',
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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setIconPath(string $iconPath): self
    {
        $this->iconPath = $iconPath;

        return $this;
    }

    public function getIcon()
    {
        if (!$this->iconPath) {
            return null;
        }
        return ORM::getOneByQuery(
            File::class,
            "
            SELECT *
            FROM `files`
            WHERE path = '" . db()->esc($this->iconPath) . "'"
        );
    }

    // General methodes
    public function getSlug(): string
    {
        return 'mærke' . $this->getId() . '-' . clearFileName($this->getTitle()) . '/';
    }

    public function getPages(string $order = 'navn')
    {
        return ORM::getByQuery(
            Page::class,
            "
            SELECT sider.*
            FROM sider
            WHERE maerke = " . $this->getId() . "
            ORDER BY sider.`" . db()->esc($order) . "` ASC
            "
        );
    }

    // ORM related functions
    public function save()
    {
        if (!$this->id) {
            db()->query(
                "
                INSERT INTO `" . self::TABLE_NAME . "` (
                    `navn`,
                    `link`,
                    `icon`
                ) VALUES (
                    '" . db()->esc($this->title) . "',
                    '" . db()->esc($this->link) . "',
                    '" . db()->esc($this->getIcon() ? $this->getIcon()->getPath() : '') . "'
                )"
            );
            $this->setId(db()->insert_id);
        } else {
            db()->query(
                "
                UPDATE `" . self::TABLE_NAME ."` SET
                    `navn` = '" . db()->esc($this->title) . "',
                    `email` = '" . db()->esc($this->email) . "',
                    `icon` = '" . db()->esc($this->getIcon() ? $this->getIcon()->getPath() : '') . "'
                WHERE `id` = " . $this->id
            );
        }
        Render::addLoadedTable(self::TABLE_NAME);
    }
}
