<?php

class Category
{
    const TABLE_NAME = 'kat';
    const HIDDEN = 0;
    const GALLERY = 1;
    const LIST = 2;

    // Backed by DB
    private $id;
    private $title;
    private $parentId;
    private $iconPath;
    private $renderMode;
    private $email;
    private $weightedChildren;
    private $weight;

    // Runtime
    private $visable;

    /**
     * Connect the database and set session to UTF-8 Danish
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTitle($data['title'])
            ->setParentId($data['parent_id'])
            ->setIconPath($data['icon_path'])
            ->setRenderMode($data['render_mode'])
            ->setEmail($data['email'])
            ->setWeightedChildren($data['oredered_children'])
            ->setWeight($data['order']);
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'                => $data['id'] ?: null,
            'title'             => $data['navn'] ?: '',
            'parent_id'         => $data['bind'] ?: 0,
            'icon_path'         => $data['icon'] ?: '',
            'render_mode'       => $data['vis'] ?: Category::HIDDEN,
            'email'             => $data['email'] ?: '',
            'oredered_children' => $data['custom_sort_subs'] ?: false,
            'order'             => $data['order'] ?: 0,
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

    public function setParentId(int $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setIconPath(string $iconPath): self
    {
        $this->iconPath = $iconPath;

        return $this;
    }

    public function getIconPath(): string
    {
        return $this->iconPath;
    }

    public function setRenderMode(int $renderMode): self
    {
        $this->renderMode = $renderMode;

        return $this;
    }

    public function getRenderMode(): int
    {
        return $this->renderMode;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setWeightedChildren(bool $weightedChildren): self
    {
        $this->weightedChildren = (int) $weightedChildren;

        return $this;
    }

    public function getWeightedChildren(): bool
    {
        return (bool) $this->weightedChildren;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    // General methodes
    public function isVisable(): bool
    {
        if ($this->renderMode === Category::HIDDEN) {
            return false;
        }

        if ($this->visable === null) {
            Cache::addLoadedTable('bind');
            if (db()->fetchOne("SELECT `id` FROM `bind` WHERE `kat` = " . $this->getId())) {
                $this->visable = true;
                return $this->visable;
            }

            foreach ($this->getChildren() as $child) {
                if ($child->isVisable()) {
                    $this->visable = true;
                    return $this->visable;
                }
            }

            $this->visable = false;
        }

        return $this->visable;
    }

    public function getSlug(bool $raw = false): string
    {
        $title = $this->getTitle();
        if (!$title && $this->getIconPath()) {
            $icon = db()->fetchOne(
                "
                SELECT `alt`
                FROM `files`
                WHERE path = '" . db()->esc($category->getIconPath()) . "'"
            );
            Cache::addLoadedTable('files');

            $title = $icon['alt'];
        }

        $slug = 'kat' . $this->getId() . '-';
        if ($raw) {
            $slug .= rawurlencode(clearFileName($title));
        } else {
            $slug .= clearFileName($title);
        }
        return $slug .= '/';
    }

    public function getParent()
    {
        if ($this->parentId > 0) {
            return ORM::getOne(self::class, $this->parentId);
        }

        return null;
    }

    public function getChildren(bool $onlyVisable = false)
    {
        $children = ORM::getByQuery(
            self::class,
            "
            SELECT *
            FROM kat
            WHERE bind = " . $this->getId() . "
            ORDER BY navn
            "
        );

        if (!$onlyVisable) {
            return $children;
        }

        foreach ($children as $key => $child) {
            if (!$child->isVisable()) {
                unset($children[$key]);
            }
        }

        return array_values($children);
    }

    public function getPages(string $order = 'navn')
    {
        Cache::addLoadedTable('bind');
        return ORM::getByQuery(
            Page::class,
            "
            SELECT sider.*
            FROM bind JOIN sider ON bind.side = sider.id
            WHERE bind.kat = " . $this->getId() . "
            ORDER BY sider.`" . db()->esc($order) . "` ASC
            "
        );
    }

    public function isInactive(): bool
    {
        $branch = $this->getBranch();
        return (bool) reset($branch)->getParentId();
    }

    public function getBranch(): array
    {
        $nodes = [];
        $category = $this;
        do {
            $nodes[] = $category;
        } while ($category = $category->getParent());

        return array_reverse($nodes);
    }

    // ORM related functions
    public function save()
    {
        if (!$this->id) {
            db()->query(
                "
                INSERT INTO `" . self::TABLE_NAME . "` (
                    `navn`,
                    `bind`,
                    `icon`,
                    `vis`,
                    `email`,
                    `custom_sort_subs`,
                    `order`
                ) VALUES ('"
                    . db()->esc($this->title) . "', "
                    . $this->parentId . ", '"
                    . db()->esc($this->iconPath) . "', "
                    . $this->renderMode . ", '"
                    . db()->esc($this->email) . "', "
                    . $this->weightedChildren . ", "
                    . $this->weight
                . ")"
            );
            $this->setId(db()->insert_id);
        } else {
            db()->query(
                "
                UPDATE `" . self::TABLE_NAME ."`
                SET `navn` = '" . db()->esc($this->title)
                . "', `bind` = " . $this->parentId
                . ", `icon` = '" . db()->esc($this->iconPath)
                . "', `vis` = " . $this->renderMode
                . ", `email` = '" . db()->esc($this->email)
                . "', `custom_sort_subs` = " . $this->weightedChildren
                . ", `order` = " . $this->weight
                . " WHERE `id` = " . $this->id
            );
        }
        Cache::addLoadedTable(self::TABLE_NAME);
    }
}
