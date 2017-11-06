<?php namespace AGCMS\Entity;

use AGCMS\ORM;
use AGCMS\Render;

class Page extends AbstractRenderable
{
    /** Table name in database. */
    const TABLE_NAME = 'sider';

    // Backed by DB
    /** @var string Stock keeping unit. */
    private $sku = '';

    /** @var int Latest save time. */
    private $timeStamp;

    /** @var string Page keywords, coma seporated. */
    private $keywords = '';

    /** @var string HTML body. */
    private $html = '';

    /** @var string Short text description. */
    private $excerpt = '';

    /** @var ?string Thumbnail path. */
    private $iconPath;

    /** @var ?int Id of requirement page. */
    private $requirementId;

    /** @var ?int Id of brand. */
    private $brandId;

    /** @var int Current price. */
    private $price = 0;

    /** @var int Previous price. */
    private $oldPrice = 0;

    /** @var int What type of price is the current (from, specific). */
    private $priceType = 0;

    /** @var int What type of price is the previous (from, specific). */
    private $oldPriceType = 0;

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setSku($data['sku'])
            ->setTimeStamp($data['timestamp'] ?? 0)
            ->setKeywords($data['keywords'])
            ->setExcerpt($data['excerpt'])
            ->setIconPath($data['icon_path'])
            ->setRequirementId($data['requirement_id'])
            ->setBrandId($data['brand_id'])
            ->setPrice($data['price'])
            ->setOldPrice($data['old_price'])
            ->setPriceType($data['price_type'])
            ->setOldPriceType($data['old_price_type'])
            ->setHtml($data['html'])
            ->setTitle($data['title'])
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
            'id'             => $data['id'],
            'sku'            => $data['varenr'],
            'timestamp'      => strtotime($data['dato']) + db()->getTimeOffset(),
            'title'          => $data['navn'],
            'keywords'       => $data['keywords'],
            'html'           => $data['text'],
            'excerpt'        => $data['beskrivelse'],
            'icon_path'      => $data['billed'],
            'requirement_id' => $data['krav'],
            'brand_id'       => $data['maerke'],
            'price'          => $data['pris'],
            'old_price'      => $data['for'],
            'price_type'     => $data['fra'],
            'old_price_type' => $data['burde'],
        ];
    }

    /**
     * Delete page and it's relations
     *
     * @return bool
     */
    public function delete(): bool
    {
        // Forget affected tables, though alter indivitual deletes will forget most
        Render::addLoadedTable('list_rows');
        db()->query('DELETE FROM `list_rows` WHERE `link` = ' . $this->getId());
        foreach ($this->getTables() as $table) {
            $table->delete();
        }

        // parent::delete will forget any binding and accessory relationship
        Render::addLoadedTable('bind');
        db()->query('DELETE FROM `bind` WHERE side = ' . $this->getId());
        Render::addLoadedTable('tilbehor');
        db()->query('DELETE FROM `tilbehor` WHERE side = ' . $this->getId() . ' OR tilbehor =' . $this->getId());

        return parent::delete();
    }

    // Getters and setters

    /**
     * Set the Stock Keeping Unit identifyer.
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
     * Get the Stock Keeping Unity.
     *
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * Set the last modefied time stamp.
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
     * Get last modefied.
     *
     * @return int
     */
    public function getTimeStamp(): int
    {
        return $this->timeStamp;
    }

    /**
     * Set keywords.
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
     * Get keywords.
     *
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * Set HTML body.
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
     * Get the HTML body.
     *
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * Set the breaf description.
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
     * Get the short description.
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

        return $this->excerpt;
    }

    /**
     * Check if an except has been entered manually.
     *
     * @return bool
     */
    public function hasExcerpt(): bool
    {
        return (bool) $this->excerpt;
    }

    /**
     * Set page thumbnail
     *
     * @param string $iconPath
     *
     * @return self
     */
    public function setIconPath(?string $iconPath): self
    {
        $this->iconPath = $iconPath;

        return $this;
    }

    /**
     * Get the file that is being used as an icon.
     *
     * @return ?File
     */
    public function getIcon(): ?File
    {
        if (null === $this->iconPath) {
            return null;
        }

        return File::getByPath($this->iconPath);
    }

    /**
     * Set the Requirement id.
     *
     * @param int Requirement id
     *
     * @return self
     */
    public function setRequirementId(?int $requirementId): self
    {
        $this->requirementId = $requirementId;

        return $this;
    }

    /**
     * Set the Brand id.
     *
     * @param int Brand id
     *
     * @return self
     */
    public function setBrandId(?int $brandId): self
    {
        $this->brandId = $brandId;

        return $this;
    }

    /**
     * Set the price.
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
     * Get the price.
     *
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * Set the old price.
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
     * Get the previous price.
     *
     * @return int
     */
    public function getOldPrice(): int
    {
        return $this->oldPrice;
    }

    /**
     * Set the price type.
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
     * Get the price Type.
     *
     * @return int
     */
    public function getPriceType(): int
    {
        return $this->priceType;
    }

    /**
     * Set the type of the privious price
     *
     * @param int $oldPriceType
     *
     * @return self
     */
    public function setOldPriceType(int $oldPriceType): self
    {
        $this->oldPriceType = $oldPriceType;

        return $this;
    }

    /**
     * Get the previous price Type.
     *
     * @return int
     */
    public function getOldPriceType(): int
    {
        return $this->oldPriceType;
    }

    // General methodes

    /**
     * Get the url slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return 'side' . $this->getId() . '-' . clearFileName($this->getTitle()) . '.html';
    }

    /**
     * Get canonical url for this entity.
     *
     * @param Category|null $category Category to base the url on
     *
     * @return string
     */
    public function getCanonicalLink(Category $category = null): string
    {
        $url = '/';
        if (!$category || !$this->isInCategory($category)) {
            $category = $this->getPrimaryCategory();
        }
        if ($category) {
            $url = $category->getCanonicalLink();
        }

        return $url . $this->getSlug();
    }

    /**
     * Check if the page i attached to a given category
     *
     * @param Category $category
     *
     * @return bool
     */
    public function isInCategory(Category $category): bool
    {
        Render::addLoadedTable('bind');

        return (bool) db()->fetchOne(
            '
            SELECT kat FROM `bind`
            WHERE side = ' . $this->getId() . '
            AND kat = ' . $category->getId()
        );
    }

    /**
     * Get the primery category for this page.
     *
     * @return ?Category
     */
    public function getPrimaryCategory(): ?Category
    {
        return ORM::getOneByQuery(Category::class, $this->getCategoriesQuery());
    }

    /**
     * Get all categories.
     *
     * @return Category[]
     */
    public function getCategories(): array
    {
        return ORM::getByQuery(Category::class, $this->getCategoriesQuery());
    }

    /**
     * Generate the query for getting all categories where this page is linke.
     *
     * @return string
     */
    private function getCategoriesQuery(): string
    {
        Render::addLoadedTable('bind');

        return 'SELECT * FROM `kat` WHERE id IN (SELECT kat FROM `bind` WHERE side = ' . $this->getId() . ')';
    }

    /**
     * Add the page to a given category
     *
     * @param Category $category
     *
     * @return void
     */
    public function addToCategory(Category $category): void
    {
        db()->query('INSERT INTO `bind` (`side`, `kat`) VALUES (' . $this->getId() . ', ' . $category->getId() . ')');
        ORM::forgetByQuery(self::class, $this->getCategoriesQuery());
    }

    /**
     * Remove the page form a given cateogory
     *
     * @param Category $category
     *
     * @return void
     */
    public function removeFromCategory(Category $category): void
    {
        db()->query('DELETE FROM `bind` WHERE `side` = ' . $this->getId() . ' AND `kat` = ' . $category->getId());
        ORM::forgetByQuery(self::class, $this->getCategoriesQuery());
    }

    /**
     * Add a page as an accessory.
     *
     * @return void
     */
    public function addAccessory(Page $accessory): void
    {
        db()->query(
            '
            INSERT IGNORE INTO `tilbehor` (`side`, `tilbehor`)
            VALUES (' . $this->getId() . ', ' . $accessory->getId() . ')'
        );

        ORM::forgetByQuery(self::class, $this->getAccessoryQuery());
    }

    /**
     * Remove an accessory from the page.
     *
     * @param Page $accessory
     *
     * @return void
     */
    public function removeAccessory(Page $accessory): void
    {
        db()->query('DELETE FROM `tilbehor` WHERE side = ' . $this->getId() . ' AND tilbehor = ' . $accessory->getId());

        ORM::forgetByQuery(self::class, $this->getAccessoryQuery());
    }

    /**
     * Get accessory pages.
     *
     * @return Page[]
     */
    public function getAccessories(): array
    {
        return ORM::getByQuery(self::class, $this->getAccessoryQuery());
    }

    /**
     * Get presentable accessories.
     *
     * @return Page[]
     */
    public function getActiveAccessories(): array
    {
        $accessories = [];
        foreach ($this->getAccessories() as $accessory) {
            if (!$accessory->isInactive()) {
                $accessories[] = $accessory;
            }
        }

        return $accessories;
    }

    /**
     * Get query for finding accessories
     *
     * @return string
     */
    private function getAccessoryQuery(): string
    {
        Render::addLoadedTable('tilbehor');

        return '
            SELECT * FROM sider
            WHERE id IN (SELECT tilbehor FROM tilbehor WHERE side = ' . $this->getId() . ') ORDER BY navn ASC';
    }

    /**
     * Get tabels.
     *
     * @return Table[]
     */
    public function getTables(): array
    {
        return ORM::getByQuery(
            Table::class,
            'SELECT * FROM `lists` WHERE page_id = ' . $this->getId()
        );
    }

    /**
     * Get product brand.
     *
     * @return ?Brand
     */
    public function getBrand(): ?Brand
    {
        if (null === $this->brandId) {
            return null;
        }

        return ORM::getOne(Brand::class, $this->brandId);
    }

    /**
     * Get product requirement.
     *
     * @return ?Requirement
     */
    public function getRequirement(): ?Requirement
    {
        if (null === $this->requirementId) {
            return null;
        }

        return ORM::getOne(Requirement::class, $this->requirementId);
    }

    /**
     * Is the product not on the website.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        $bind = db()->fetchOne('SELECT kat FROM `bind` WHERE kat < 1 AND side = ' . $this->getId());
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
     * Get data in array format for the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
    {
        $this->setTimeStamp(time());

        return [
            'dato'        => 'NOW()',
            'navn'        => db()->eandq($this->title),
            'keywords'    => db()->eandq($this->keywords),
            'text'        => db()->eandq($this->html),
            'varenr'      => db()->eandq($this->sku),
            'beskrivelse' => db()->eandq($this->excerpt),
            'billed'      => null !== $this->iconPath ? db()->eandq($this->iconPath) : 'NULL',
            'krav'        => null !== $this->requirementId ? $this->requirementId : 'NULL',
            'maerke'      => null !== $this->brandId ? $this->brandId : 'NULL',
            'pris'        => $this->price,
            'for'         => $this->oldPrice,
            'fra'         => $this->priceType,
            'burde'       => $this->oldPriceType,
        ];
    }
}
