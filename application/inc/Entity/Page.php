<?php

class Category
{
    const TABLE_NAME = 'side';

    // Backed by DB
    private $id;
    private $timestamp;
    private $title;
    private $keywords;
    private $price;
    private $html;
    private $sku;
    private $old_price;
    private $requirement_id;
    private $krav;
    private $brand_id;
    private $image_path;
    private $price_type;
    private $old_price_type;

    // Runtime
    private $visable;

    /**
     * Connect the database and set session to UTF-8 Danish
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTitle($data['title']);
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'             => $data['id'] ?: null,
            'timestamp'      => $data['dato'] ? strtotim($data['dato']) : 0,
            'title'          => $data['navn'] ?: '',
            'keywords'       => $data['keywords'] ?? '',
            'price'          => $data['pris'] ?? 0,
            'html'           => $data['text'] ?? '',
            'sku'            => $data['varenr'] ?? '',
            'old_price'      => $data['for'] ?? 0,
            'excerpt'        => $data['beskrivelse'] ?? '',
            'requirement_id' => $data['krav'] ?? 0,
            'brand_id'       => $data['maerke'] ?? 0,
            'image_path'     => $data['billed'] ?? '',
            'price_type'     => $data['fra'] ?? 0,
            'old_price_type' => $data['burde'] ?? 0,
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

    public function getSlug(bool $raw = false): string
    {
        $slug = 'side' . $kat->getId() . '-';
        if ($raw) {
            $slug .= rawurlencode(clearFileName($this->getTitle()));
        } else {
            $slug .= clearFileName($this->getTitle());
        }
        return $slug .= '/';
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
