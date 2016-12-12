<?php

class Page extends AbstractRenderable
{
    /**
     * Table name in database
     */
    const TABLE_NAME = 'sider';

    // Backed by DB
    /**
     * Stock keeping unit
     */
    private $sku;

    /**
     * Latest save time
     */
    private $timeStamp;

    /**
     * Page keywords, coma seporated
     */
    private $keywords;

    /**
     * HTML body
     */
    private $html;

    /**
     * Short text description
     */
    private $excerpt;

    /**
     * Thumbnail path
     */
    private $imagePath;

    /**
     * Id of requirement page
     */
    private $requirementId;

    /**
     * Id of brand
     */
    private $brandId;

    /**
     * Current price
     */
    private $price;

    /**
     * Previous price
     */
    private $oldPrice;

    /**
     * What type of price is the current (from, specific)
     */
    private $priceType;

    /**
     * What type of price is the previous (from, specific)
     */
    private $oldPriceType;

    /**
     * Construct the entity
     *
     * @param array $data The entity data
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
            'id'             => $data['id'],
            'sku'            => $data['varenr'],
            'timestamp'      => strtotime($data['dato']) + db()->getTimeOffset(),
            'title'          => $data['navn'],
            'keywords'       => $data['keywords'],
            'html'           => $data['text'],
            'excerpt'        => $data['beskrivelse'],
            'image_path'     => $data['billed'],
            'requirement_id' => $data['krav'],
            'brand_id'       => $data['maerke'],
            'price'          => $data['pris'],
            'old_price'      => $data['for'],
            'price_type'     => $data['fra'],
            'old_price_type' => $data['burde'],
        ];
    }

    // Getters and setters
    /**
     * Set the Stock Keeping Unit identifyer
     *
     * @param string $sku Stock product number
     *
     * @return self
     */
    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get the Stock Keeping Unity
     *
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * Set the last modefied time stamp
     *
     * @param int $timeStamp Last modefied
     *
     * @return self
     */
    public function setTimeStamp(int $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    /**
     * Get last modefied
     *
     * @return int
     */
    public function getTimeStamp(): int
    {
        return $this->timeStamp;
    }

    /**
     * Set keywords
     *
     * @param string $keywords Comma seporated
     *
     * @return self
     */
    public function setKeywords(string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords
     *
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * Set HTML body
     *
     * @param string $html The HTML body
     *
     * @return self
     */
    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get the HTML body
     *
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * Set the breaf description
     *
     * @param string $excerpt Short text
     *
     * @return self
     */
    public function setExcerpt(string $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    /**
     * Get the short description
     *
     * @return string
     */
    public function getExcerpt(): string
    {
        if (!$this->excerpt) {
            $excerpt = preg_replace(['/</', '/>/', '/\s+/'], [' <', '> ', ' '], $this->html);
            $excerpt = strip_tags($excerpt);
            $excerpt = preg_replace('/\s+/', ' ', $excerpt);
            return stringLimit($excerpt, 100);
        }

        return strip_tags($this->excerpt);
    }

    /**
     * Set the image file path
     *
     * @param strig $imagePath Thumbnail file path
     *
     * @return self
     */
    public function setImagePath(string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * Get image file path
     *
     * @return string
     */
    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    /**
     * Set the Requirement id
     *
     * @param int Requirement id
     *
     * @return self
     */
    public function setRequirementId(int $requirementId): self
    {
        $this->requirementId = $requirementId;

        return $this;
    }

    /**
     * Set the Brand id
     *
     * @param int Brand id
     *
     * @return self
     */
    public function setBrandId(int $brandId): self
    {
        $this->brandId = $brandId;

        return $this;
    }

    /**
     * Get the Brand Id
     *
     * @return int
     */
    public function getBrandId(): int
    {
        return $this->brandId;
    }

    /**
     * Set the price
     *
     * @param int $price Price
     *
     * @return self
     */
    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get the price
     *
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * Set the old price
     *
     * @param int $oldPrice The previous price
     *
     * @return self
     */
    public function setOldPrice(int $oldPrice): self
    {
        $this->oldPrice = $oldPrice;

        return $this;
    }

    /**
     * Get the previous price
     *
     * @return int
     */
    public function getOldPrice(): int
    {
        return $this->oldPrice;
    }

    /**
     * Set the price type
     *
     * @param int $priceType The price type
     *
     * @return self
     */
    public function setPriceType(int $priceType): self
    {
        $this->priceType = $priceType;

        return $this;
    }

    /**
     * Get the price Type
     *
     * @return int
     */
    public function getPriceType(): int
    {
        return $this->priceType;
    }

    /**
     * Set the previous price type
     *
     * @param int $priceType The previous price type
     *
     * @return self
     */
    public function setOldPriceType(int $oldPriceType): self
    {
        $this->oldPriceType = $oldPriceType;

        return $this;
    }

    /**
     * Get the previous price Type
     *
     * @return int
     */
    public function getOldPriceType(): int
    {
        return $this->oldPriceType;
    }

    // General methodes
    /**
     * Get the url slug
     *
     * @return string
     */
    public function getSlug(): string
    {
        return 'side' . $this->getId() . '-' . clearFileName($this->getTitle()) . '.html';
    }

    /**
     * Get canonical url for this entity
     *
     * @param \Category $category Category to base the url on
     *
     * @return string
     */
    public function getCanonicalLink(Category $category = null): string
    {
        $url = '/';
        if (!$category) {
            $category = $this->getPrimaryCategory();
        }
        if ($category) {
            $url = $category->getCanonicalLink();
        }

        return $url . $this->getSlug();
    }

    /**
     * Is the page in the given category
     *
     * @param int $categoryId Id of category to check in
     *
     * @return bool
     */
    public function isInCategory(int $categoryId): bool
    {
        Render::addLoadedTable('bind');
        return (bool) db()->fetchOne(
            "
            SELECT id FROM `bind`
            WHERE side = " . $this->getId() . "
            AND kat = " . $categoryId
        );
    }

    /**
     * Get the primery category for this page
     *
     * @return ?Category
     */
    public function getPrimaryCategory()
    {
        Render::addLoadedTable('bind');
        return ORM::getOneByQuery(
            Category::class,
            "
            SELECT kat.*
            FROM `bind`
            JOIN kat ON kat.id = bind.kat
            WHERE bind.side = " . $this->getId()
        );
    }

    /**
     * Get all categories
     *
     * @return array
     */
    public function getCategories(): array
    {
        Render::addLoadedTable('bind');
        return ORM::getByQuery(
            Category::class,
            "
            SELECT kat.*
            FROM `bind`
            JOIN kat ON kat.id = bind.kat
            WHERE bind.side = " . $this->getId()
        );
    }

    /**
     * Get accessory pages
     *
     * @return array
     */
    public function getAccessories(): array
    {
        Render::addLoadedTable('tilbehor');
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

    /**
     * Get tabels
     *
     * @return array
     */
    public function getTables(): array
    {
        return ORM::getByQuery(
            Table::class,
            "SELECT * FROM `lists` WHERE page_id = " . $this->getId()
        );
    }

    /**
     * Get product brand
     *
     * @return ?Brand
     */
    public function getBrand()
    {
        return $this->brandId ? ORM::getOne(Brand::class, $this->brandId) : null;
    }

    /**
     * Get product requirement
     *
     * @return ?Requirement
     */
    public function getRequirement()
    {
        return $this->requirementId ? ORM::getOne(Requirement::class, $this->requirementId) : null;
    }

    /**
     * Is the product not on the website
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        $bind = db()->fetchOne("SELECT kat FROM bind WHERE kat < 1 AND side = " . $this->getId());
        Render::addLoadedTable('bind');
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
    /**
     * Save entity to database
     *
     * @return self
     */
    public function save(): InterfaceEntity
    {
        if ($this->id === null) {
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
                    '" . db()->esc($this->title) . "',
                    '" . db()->esc($this->keywords) . "',
                    " . $this->price . ",
                    '" . db()->esc($this->html) . "',
                    '" . db()->esc($this->sku) . "',
                    " . $this->oldPrice . ",
                    '" . db()->esc($this->excerpt) . "',
                    " . ($this->getRequirement() ? $this->requirementId : 0) . ",
                    " . ($this->getBrand() ? $this->brandId : 0) . ",
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
                UPDATE `" . self::TABLE_NAME . "` SET
                    `dato` = NOW(),
                    `navn` = '" . db()->esc($this->title) . "',
                    `varenr` = '" . db()->esc($this->sku) . "',
                    `keywords` = '" . db()->esc($this->keywords) . "',
                    `text` = '" . db()->esc($this->html) . "',
                    `beskrivelse` = '" . db()->esc($this->excerpt) . "',
                    `billed` = '" . db()->esc($this->imagePath) . "',
                    `krav` = " . ($this->getRequirement() ? $this->requirementId : 0) . ",
                    `maerke` = " . ($this->getBrand() ? $this->brandId : 0) . ",
                    `pris` = " . $this->price . ",
                    `for` = " . $this->oldPrice . ",
                    `fra` = " . $this->priceType . ",
                    `burde` = " . $this->oldPriceType
                . " WHERE `id` = " . $this->id
            );
        }
        $this->setTimeStamp(time());
        Render::addLoadedTable(self::TABLE_NAME);

        return $this;
    }
}
