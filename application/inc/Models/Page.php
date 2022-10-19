<?php

namespace App\Models;

use App\Exceptions\Exception;
use App\Services\DbService;
use App\Services\OrmService;

class Page extends AbstractRenderable implements InterfaceRichText
{
    use HasIcon;

    /** Table name in database. */
    public const TABLE_NAME = 'sider';

    // Backed by DB

    /** @var string Stock keeping unit. */
    private string $sku = '';

    /** @var int Latest save time. */
    private int $timeStamp;

    /** @var string Page keywords, coma seporated. */
    private string $keywords = '';

    /** @var string HTML body. */
    private string $html = '';

    /** @var string Short text description. */
    private string $excerpt = '';

    /** @var ?int Id of requirement page. */
    private ?int $requirementId;

    /** @var ?int Id of brand. */
    private ?int $brandId;

    /** @var int Current price. */
    private int $price = 0;

    /** @var int Previous price. */
    private int $oldPrice = 0;

    /** @var int What type of price is the current (from, specific). */
    private int $priceType = 0;

    /** @var int What type of price is the previous (from, specific). */
    private int $oldPriceType = 0;

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
        return [
            'id'             => $data['id'],
            'sku'            => $data['varenr'],
            'timestamp'      => strtotime($data['dato']) + app(DbService::class)->getTimeOffset(),
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
     */
    public function delete(): bool
    {
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
     * @return $this
     */
    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get the Stock Keeping Unity.
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * Set the last modefied time stamp.
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
     */
    public function getTimeStamp(): int
    {
        return $this->timeStamp;
    }

    /**
     * @param string $keywords Comma seporated
     *
     * @return $this
     */
    public function setKeywords(string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * @return $this
     */
    public function setHtml(string $html): InterfaceRichText
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get the HTML body.
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * Set the breaf description.
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
     */
    public function hasExcerpt(): bool
    {
        return (bool) $this->excerpt;
    }

    /**
     * @return $this
     */
    public function setRequirementId(?int $requirementId): self
    {
        $this->requirementId = $requirementId;

        return $this;
    }

    /**
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
     * @return $this
     */
    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get the price.
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
     */
    public function getOldPrice(): int
    {
        return $this->oldPrice;
    }

    /**
     * Set the price type.
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
     */
    public function getPriceType(): int
    {
        return $this->priceType;
    }

    /**
     * Set the type of the privious price.
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
     */
    public function getOldPriceType(): int
    {
        return $this->oldPriceType;
    }

    // General methods

    /**
     * Get the url slug.
     */
    public function getSlug(): string
    {
        return 'side' . $this->getId() . '-' . cleanFileName($this->getTitle()) . '.html';
    }

    /**
     * Get canonical url for this entity.
     *
     * @param null|Category $category Category to base the url on
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
     */
    public function isInCategory(Category $category): bool
    {
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
        $category = app(OrmService::class)->getOneByQuery(Category::class, $this->getCategoriesQuery());

        return $category;
    }

    /**
     * Get all categories.
     *
     * @return Category[]
     */
    public function getCategories(): array
    {
        $categories = app(OrmService::class)->getByQuery(Category::class, $this->getCategoriesQuery());

        return $categories;
    }

    /**
     * Generate the query for getting all categories where this page is linke.
     */
    private function getCategoriesQuery(): string
    {
        app(DbService::class)->addLoadedTable('bind');

        return 'SELECT * FROM `kat` WHERE id IN (SELECT kat FROM `bind` WHERE side = ' . $this->getId() . ')';
    }

    /**
     * Add the page to a given category.
     */
    public function addToCategory(Category $category): void
    {
        app(DbService::class)->query(
            'INSERT INTO `bind` (`side`, `kat`) VALUES (' . $this->getId() . ', ' . $category->getId() . ')'
        );
        app(OrmService::class)->forgetByQuery(self::class, $this->getCategoriesQuery());
    }

    /**
     * Remove the page form a given cateogory.
     */
    public function removeFromCategory(Category $category): void
    {
        app(DbService::class)->query('DELETE FROM `bind` WHERE `side` = ' . $this->getId() . ' AND `kat` = ' . $category->getId());
        app(OrmService::class)->forgetByQuery(self::class, $this->getCategoriesQuery());
    }

    /**
     * Add a page as an accessory.
     */
    public function addAccessory(self $accessory): void
    {
        app(DbService::class)->query(
            '
            INSERT IGNORE INTO `tilbehor` (`side`, `tilbehor`)
            VALUES (' . $this->getId() . ', ' . $accessory->getId() . ')'
        );

        app(OrmService::class)->forgetByQuery(self::class, $this->getAccessoryQuery());
    }

    /**
     * Remove an accessory from the page.
     */
    public function removeAccessory(self $accessory): void
    {
        app(DbService::class)->query(
            'DELETE FROM `tilbehor` WHERE side = ' . $this->getId() . ' AND tilbehor = ' . $accessory->getId()
        );

        app(OrmService::class)->forgetByQuery(self::class, $this->getAccessoryQuery());
    }

    /**
     * Get accessory pages.
     *
     * @return Page[]
     */
    public function getAccessories(): array
    {
        $page = app(OrmService::class)->getByQuery(self::class, $this->getAccessoryQuery());

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
     */
    private function getAccessoryQuery(): string
    {
        app(DbService::class)->addLoadedTable('tilbehor');

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
        $tables = app(OrmService::class)->getByQuery(
            Table::class,
            'SELECT * FROM `lists` WHERE page_id = ' . $this->getId()
        );

        return $tables;
    }

    /**
     * Check if there is a product table attached to this page.
     */
    public function hasProductTable(): bool
    {
        foreach ($this->getTables() as $table) {
            if ($table->hasPrices()) {
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
            $brand = app(OrmService::class)->getOne(Brand::class, $this->brandId);
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
            $requirement = app(OrmService::class)->getOne(Requirement::class, $this->requirementId);
        }

        return $requirement;
    }

    /**
     * Is the product not on the website.
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
