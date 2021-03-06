<?php namespace App\Models;

use App\Exceptions\Exception;
use App\Services\DbService;
use App\Services\OrmService;

class Page extends AbstractRenderable implements InterfaceRichText
{
    use HasIcon;

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

    public function __construct(array $data = [])
    {
        $this->iconId = $data['icon_id'];
        $this->setSku($data['sku'])
            ->setTimeStamp($data['timestamp'] ?? 0)
            ->setKeywords($data['keywords'])
            ->setExcerpt($data['excerpt'])
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

    public static function mapFromDB(array $data): array
    {
        /** @var DbService */
        $db = app(DbService::class);

        return [
            'id'             => $data['id'],
            'sku'            => $data['varenr'],
            'timestamp'      => strtotime($data['dato']) + $db->getTimeOffset(),
            'title'          => $data['navn'],
            'keywords'       => $data['keywords'],
            'html'           => $data['text'],
            'excerpt'        => $data['beskrivelse'],
            'icon_id'        => $data['icon_id'],
            'requirement_id' => $data['krav'],
            'brand_id'       => $data['maerke'],
            'price'          => $data['pris'],
            'old_price'      => $data['for'],
            'price_type'     => $data['fra'],
            'old_price_type' => $data['burde'],
        ];
    }

    /**
     * Delete page and it's relations.
     *
     * @return bool
     */
    public function delete(): bool
    {
        /** @var DbService */
        $db = app(DbService::class);

        // Forget affected tables, though alter indivitual deletes will forget most
        $db->addLoadedTable('list_rows');
        $db->query('DELETE FROM `list_rows` WHERE `link` = ' . $this->getId());
        foreach ($this->getTables() as $table) {
            $table->delete();
        }

        // parent::delete will forget any binding and accessory relationship
        $db->addLoadedTable('bind');
        $db->query('DELETE FROM `bind` WHERE side = ' . $this->getId());
        $db->addLoadedTable('tilbehor');
        $db->query('DELETE FROM `tilbehor` WHERE side = ' . $this->getId() . ' OR tilbehor =' . $this->getId());

        return parent::delete();
    }

    // Getters and setters

    /**
     * Set the Stock Keeping Unit identifyer.
     *
     * @param string $sku Stock product number
     *
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setHtml(string $html): InterfaceRichText
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
     * @return $this
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
            if (null === $excerpt) {
                throw new Exception('preg_replace failed');
            }
            $excerpt = strip_tags($excerpt);
            $excerpt = preg_replace('/\s+/', ' ', $excerpt);
            if (null === $excerpt) {
                throw new Exception('preg_replace failed');
            }

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
     * Set the Requirement id.
     *
     * @param ?int $requirementId Requirement id
     *
     * @return $this
     */
    public function setRequirementId(?int $requirementId): self
    {
        $this->requirementId = $requirementId;

        return $this;
    }

    /**
     * Set the Brand id.
     *
     * @param ?int $brandId Brand id
     *
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * Set the type of the privious price.
     *
     * @param int $oldPriceType
     *
     * @return $this
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

    // General methods

    /**
     * Get the url slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return 'side' . $this->getId() . '-' . cleanFileName($this->getTitle()) . '.html';
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
     * Check if the page i attached to a given category.
     *
     * @param Category $category
     *
     * @return bool
     */
    public function isInCategory(Category $category): bool
    {
        /** @var DbService */
        $db = app(DbService::class);

        $db->addLoadedTable('bind');

        return (bool) $db->fetchOne(
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
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Category */
        $category = $orm->getOneByQuery(Category::class, $this->getCategoriesQuery());

        return $category;
    }

    /**
     * Get all categories.
     *
     * @return Category[]
     */
    public function getCategories(): array
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Category[] */
        $categories = $orm->getByQuery(Category::class, $this->getCategoriesQuery());

        return $categories;
    }

    /**
     * Generate the query for getting all categories where this page is linke.
     *
     * @return string
     */
    private function getCategoriesQuery(): string
    {
        /** @var DbService */
        $db = app(DbService::class);

        $db->addLoadedTable('bind');

        return 'SELECT * FROM `kat` WHERE id IN (SELECT kat FROM `bind` WHERE side = ' . $this->getId() . ')';
    }

    /**
     * Add the page to a given category.
     *
     * @param Category $category
     *
     * @return void
     */
    public function addToCategory(Category $category): void
    {
        /** @var DbService */
        $db = app(DbService::class);

        /** @var OrmService */
        $orm = app(OrmService::class);

        $db->query(
            'INSERT INTO `bind` (`side`, `kat`) VALUES (' . $this->getId() . ', ' . $category->getId() . ')'
        );
        $orm->forgetByQuery(self::class, $this->getCategoriesQuery());
    }

    /**
     * Remove the page form a given cateogory.
     *
     * @param Category $category
     *
     * @return void
     */
    public function removeFromCategory(Category $category): void
    {
        /** @var DbService */
        $db = app(DbService::class);

        /** @var OrmService */
        $orm = app(OrmService::class);

        $db->query('DELETE FROM `bind` WHERE `side` = ' . $this->getId() . ' AND `kat` = ' . $category->getId());
        $orm->forgetByQuery(self::class, $this->getCategoriesQuery());
    }

    /**
     * Add a page as an accessory.
     *
     * @return void
     */
    public function addAccessory(self $accessory): void
    {
        /** @var DbService */
        $db = app(DbService::class);

        /** @var OrmService */
        $orm = app(OrmService::class);

        $db->query(
            '
            INSERT IGNORE INTO `tilbehor` (`side`, `tilbehor`)
            VALUES (' . $this->getId() . ', ' . $accessory->getId() . ')'
        );

        $orm->forgetByQuery(self::class, $this->getAccessoryQuery());
    }

    /**
     * Remove an accessory from the page.
     *
     * @param Page $accessory
     *
     * @return void
     */
    public function removeAccessory(self $accessory): void
    {
        /** @var DbService */
        $db = app(DbService::class);

        /** @var OrmService */
        $orm = app(OrmService::class);

        $db->query(
            'DELETE FROM `tilbehor` WHERE side = ' . $this->getId() . ' AND tilbehor = ' . $accessory->getId()
        );

        $orm->forgetByQuery(self::class, $this->getAccessoryQuery());
    }

    /**
     * Get accessory pages.
     *
     * @return Page[]
     */
    public function getAccessories(): array
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Page[] */
        $page = $orm->getByQuery(self::class, $this->getAccessoryQuery());

        return $page;
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
     * Get query for finding accessories.
     *
     * @return string
     */
    private function getAccessoryQuery(): string
    {
        /** @var DbService */
        $db = app(DbService::class);

        $db->addLoadedTable('tilbehor');

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
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Table[] */
        $tables = $orm->getByQuery(
            Table::class,
            'SELECT * FROM `lists` WHERE page_id = ' . $this->getId()
        );

        return $tables;
    }

    /**
     * Check if there is a product table attached to this page.
     *
     * @return bool
     */
    public function hasProductTable(): bool
    {
        foreach ($this->getTables() as $table) {
            if ($table->hasPrices() && $table->hasPrices()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get product brand.
     *
     * @return ?Brand
     */
    public function getBrand(): ?Brand
    {
        $brand = null;
        if (null !== $this->brandId) {
            /** @var OrmService */
            $orm = app(OrmService::class);

            /** @var ?Brand */
            $brand = $orm->getOne(Brand::class, $this->brandId);
        }

        return $brand;
    }

    /**
     * Get product requirement.
     *
     * @return ?Requirement
     */
    public function getRequirement(): ?Requirement
    {
        $requirement = null;
        if (null !== $this->requirementId) {
            /** @var OrmService */
            $orm = app(OrmService::class);

            /** @var ?Requirement */
            $requirement = $orm->getOne(Requirement::class, $this->requirementId);
        }

        return $requirement;
    }

    /**
     * Is the product not on the website.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        $category = $this->getPrimaryCategory();
        if ($category) {
            return $category->isInactive();
        }

        return true;
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $this->setTimeStamp(time());

        /** @var DbService */
        $db = app(DbService::class);

        return [
            'dato'        => $db->getNowValue(),
            'navn'        => $db->quote($this->title),
            'keywords'    => $db->quote($this->keywords),
            'text'        => $db->quote($this->html),
            'varenr'      => $db->quote($this->sku),
            'beskrivelse' => $db->quote($this->excerpt),
            'icon_id'     => null !== $this->iconId ? (string) $this->iconId : 'NULL',
            'krav'        => null !== $this->requirementId ? (string) $this->requirementId : 'NULL',
            'maerke'      => null !== $this->brandId ? (string) $this->brandId : 'NULL',
            'pris'        => (string) $this->price,
            'for'         => (string) $this->oldPrice,
            'fra'         => (string) $this->priceType,
            'burde'       => (string) $this->oldPriceType,
        ];
    }
}
