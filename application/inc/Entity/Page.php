<?php

class Page
{
    const TABLE_NAME = 'sider';

    // Backed by DB
    private $id;
    private $sku;
    private $timeStamp;
    private $title;
    private $keywords;
    private $html;
    private $excerpt;
    private $imagePath;
    private $requirementId;
    private $brandId;
    private $price;
    private $oldPrice;
    private $priceType;
    private $oldPriceType;

    /**
     * Connect the database and set session to UTF-8 Danish
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setSku($data['sku'])
            ->setTimeStamp($data['timestamp'])
            ->setTitle($data['title'])
            ->setKeywords($data['keywords'])
            ->setHtml($data['html'])
            ->setExcerpt($data['excerpt'])
            ->setImagePath($data['image_path'])
            ->setRequirementId($data['requirement_id'])
            ->setBrandId($data['brand_id'])
            ->setPrice($data['price'])
            ->setOldPrice($data['old_price'])
            ->setPriceType($data['price_type'])
            ->setOldPriceType($data['old_price_type']);
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'             => $data['id'] ?: null,
            'sku'            => $data['varenr'] ?: '',
            'timestamp'      => $data['dato'] ? strtotime($data['dato']) + db()->getTimeOffset() : 0,
            'title'          => $data['navn'] ?: '',
            'keywords'       => $data['keywords'] ?: '',
            'html'           => $data['text'] ?: '',
            'excerpt'        => $data['beskrivelse'] ?: '',
            'image_path'     => $data['billed'] ?: '',
            'requirement_id' => $data['krav'] ?: 0,
            'brand_id'       => $data['maerke'] ?: 0,
            'price'          => $data['pris'] ?: 0,
            'old_price'      => $data['for'] ?: 0,
            'price_type'     => $data['fra'] ?: 0,
            'old_price_type' => $data['burde'] ?: 0,
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

    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setTimeStamp(int $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    public function getTimeStamp(): int
    {
        return $this->timeStamp;
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

    public function setKeywords(string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function setExcerpt(string $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getExcerpt(): string
    {
        if (!$this->excerpt) {
            $excerpt = preg_replace(['/</', '/>/', '/\s+/'], [' <', '> ', ' '], $this->html);
            $excerpt = strip_tags($excerpt);
            $excerpt = preg_replace('/\s+/', ' ', $excerpt);
            return stringLimit($excerpt, 100);
        }

        return $this->excerpt;
    }

    public function setImagePath(string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    public function setRequirementId(int $requirementId): self
    {
        $this->requirementId = $requirementId;

        return $this;
    }

    public function getRequirementId(): int
    {
        return $this->requirementId;
    }

    public function setBrandId(int $brandId): self
    {
        $this->brandId = $brandId;

        return $this;
    }

    public function getBrandId(): int
    {
        return $this->brandId;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setOldPrice(int $oldPrice): self
    {
        $this->oldPrice = $oldPrice;

        return $this;
    }

    public function getOldPrice(): int
    {
        return $this->oldPrice;
    }

    public function setPriceType(int $priceType): self
    {
        $this->priceType = $priceType;

        return $this;
    }

    public function getPriceType(): int
    {
        return $this->priceType;
    }

    public function setOldPriceType(int $oldPriceType): self
    {
        $this->oldPriceType = $oldPriceType;

        return $this;
    }

    public function getOldPriceType(): int
    {
        return $this->oldPriceType;
    }

    // General methodes
    public function getSlug(): string
    {
        return 'side' . $this->getId() . '-' . clearFileName($this->getTitle()) . '.html';
    }

    public function getCanonicalLink(Category $category = null): string
    {
        $url = '/';

        if (!$category) {
            $category = $this->getPrimaryCategory();
        }
        if ($category) {
            $url .= $category->getSlug();
        }

        return $url . $this->getSlug();
    }

    public function getPrimaryCategory()
    {
        Cache::addLoadedTable('bind');
        return ORM::getOneByQuery(
            Category::class,
            "
            SELECT kat.*
            FROM `bind`
            JOIN kat ON kat.id = bind.kat
            WHERE bind.side = " . $this->getId()
        );
    }

    public function getCategories(): array
    {
        Cache::addLoadedTable('bind');
        return ORM::getByQuery(
            Category::class,
            "
            SELECT kat.*
            FROM `bind`
            JOIN kat ON kat.id = bind.kat
            WHERE bind.side = " . $this->getId()
        );
    }

    public function getAccessories()
    {
        Cache::addLoadedTable('tilbehor');
        return ORM::getByQuery(
            Page::class,
            "
            SELECT sider.*
            FROM tilbehor
            JOIN sider ON tilbehor.tilbehor = sider.id
            JOIN bind ON bind.side = sider.id
            WHERE tilbehor.`side` = " . $this->getId() . "
            ORDER BY sider.navn ASC
            "
        );
    }

    public function isInactive(): bool
    {
        $bind = db()->fetchOne("SELECT kat FROM bind WHERE kat < 1 AND side = " . $this->getId());
        Cache::addLoadedTable('bind');
        if ($bind) {
            return (bool) $bind['kat'];
        }

        $category = $this->getPrimaryCategory();
        if ($category) {
            return $category->isInactive();
        }

        return true;
    }

    // ORM related functions
    public function save()
    {
        if (!$this->id) {
            db()->query(
                "
                INSERT INTO `" . self::TABLE_NAME . "` (
                    `dato`,
                    `navn`,
                    `keywords`,
                    `pris`,
                    `text`,
                    `varenr`,
                    `for`,
                    `beskrivelse`,
                    `krav`,
                    `maerke`,
                    `billed`,
                    `fra`,
                    `burde`
                ) VALUES (
                    NOW(),
                    '" . db()->esc($this->name) . "',
                    '" . db()->esc($this->keywords) . "',
                    " . $this->price . ",
                    '" . db()->esc($this->html) . "',
                    '" . db()->esc($this->sku) . "',
                    " . $this->oldPrice . ",
                    '" . db()->esc($this->excerpt) . "',
                    " . $this->requirementId . ",
                    " . $this->brandId . ",
                    '" . db()->esc($this->imagePath) . "',
                    " . $this->priceType . ",
                    " . $this->oldPriceType . "
                )
                "
            );
            $this->setId(db()->insert_id);
        } else {
            db()->query(
                "
                UPDATE `" . self::TABLE_NAME ."`
                SET `dato` = NOW(),
                `navn` = '" . db()->esc($this->title)
                . "', `varenr` = '" . db()->esc($this->sku)
                . "', `keywords` = '" . db()->esc($this->keywords)
                . "', `text` = '" . db()->esc($this->html)
                . "', `beskrivelse` = '" . db()->esc($this->excerpt)
                . "', `billed` = '" . db()->esc($this->imagePath)
                . "', `krav` = " . $this->requirementId
                . ", `maerke` = " . $this->brandId
                . ", `pris` = " . $this->price
                . ", `for` = " . $this->oldPrice
                . ", `fra` = " . $this->priceType
                . ", `burde` = " . $this->old_priceType
                . " WHERE `id` = " . $this->id
            );
        }
        Cache::addLoadedTable(self::TABLE_NAME);
    }
}
