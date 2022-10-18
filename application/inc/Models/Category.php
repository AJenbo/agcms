<?php

namespace App\Models;

use App\Exceptions\InvalidInput;
use App\Render;
use App\Services\DbService;
use App\Services\OrmService;

class Category extends AbstractRenderable
{
    use HasIcon;

    /** Table name in database. */
    public const TABLE_NAME = 'kat';

    /** Do not show category. */
    public const HIDDEN = 0;

    /** Gallery rendering of pages. */
    public const GALLERY = 1;

    /** List rendering of pages. */
    public const LIST = 2;

    // Backed by DB

    /** @var ?int */
    private $parentId;

    /** @var int Render mode for page list. */
    private $renderMode = 1;

    /** @var string Contact email. */
    private $email = '';

    /** @var bool Are children to be fetched by weight. */
    private $weightedChildren = false;

    /** @var int Sorting weight. */
    private $weight = 0;

    // Runtime

    /** @var ?bool Cache if category is visible or not. */
    private $visible;

    /**
     * @param array<string, mixed> $data The entity data
     *
     * @throws InvalidInput
     */
    public function __construct(array $data = [])
    {
        $this->setRenderMode($data['render_mode'])
            ->setEmail($data['email'])
            ->setWeightedChildren($data['weighted_children'])
            ->setWeight($data['weight'])
            ->setTitle($data['title'])
            ->setId($data['id'] ?? null);

        $this->iconId = ((int) $data['icon_id']) ?: null;
        $this->parentId = null !== $data['parent_id'] ? (int) $data['parent_id'] : null;
        if (null === $this->parentId && $this->id > 0) {
            throw new InvalidInput(_('Cannot create root categories.'), 423);
        }
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'                => $data['id'],
            'title'             => $data['navn'],
            'parent_id'         => $data['bind'],
            'icon_id'           => $data['icon_id'],
            'render_mode'       => $data['vis'],
            'email'             => $data['email'],
            'weighted_children' => $data['custom_sort_subs'],
            'weight'            => $data['order'],
        ];
    }

    /**
     * Delete category and all of it's content.
     *
     * Not that this deletes all pages, even if they aren't exclusive to this category.
     */
    public function delete(): bool
    {
        foreach ($this->getChildren() as $child) {
            $child->delete();
        }

        foreach ($this->getPages() as $page) {
            $page->delete();
        }

        return parent::delete();
    }

    /**
     * Set parent.
     *
     * @param ?self $parent
     *
     * @throws InvalidInput
     *
     * @return $this
     */
    public function setParent(?self $parent): self
    {
        if (null === $this->parentId && $parent) {
            throw new InvalidInput(_('You\'r not allowed to move root categories.'), 423);
        }
        if (null !== $this->parentId && !$parent) {
            throw new InvalidInput(_('You cannot create new root categories.'), 423);
        }

        $this->parentId = $parent ? $parent->getId() : null;

        return $this;
    }

    /**
     * Set render mode.
     *
     * @param int $renderMode The render mode for displaying pages
     *
     * @return $this
     */
    public function setRenderMode(int $renderMode): self
    {
        $this->renderMode = $renderMode;

        return $this;
    }

    /**
     * Get the page list rendermode for this category.
     */
    public function getRenderMode(): int
    {
        return $this->renderMode;
    }

    /**
     * Set contact email.
     *
     * @param string $email Contact email
     *
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the contact email address for pages in this category.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set if children should be manually ordered.
     *
     * @param bool $weightedChildren Weather child categories should be ordered by weight
     *
     * @return $this
     */
    public function setWeightedChildren(bool $weightedChildren): self
    {
        $this->weightedChildren = $weightedChildren;

        return $this;
    }

    /**
     * Are the children of this category be manually ordered.
     */
    public function hasWeightedChildren(): bool
    {
        return $this->weightedChildren;
    }

    /**
     * Set weight.
     *
     * @param int $weight Order-by weight
     *
     * @return $this
     */
    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    // General methods

    /**
     * Should the category be visible on the website (is it empty or hidden).
     */
    public function isVisible(): bool
    {
        if (self::HIDDEN === $this->renderMode) {
            return false;
        }

        if (null === $this->visible) {
            if (!$this->isInactive() && ($this->hasPages() || $this->hasVisibleChildren())) {
                return true;
            }

            $this->visible = false;
        }

        return $this->visible;
    }

    /**
     * Get the url slug.
     */
    public function getSlug(): string
    {
        $title = $this->getTitle();
        if (!$title && $icon = $this->getIcon()) {
            $title = $icon->getDescription();
            if (!$title) {
                $title = pathinfo($icon->getPath(), PATHINFO_FILENAME);
            }
        }

        if (!$this->getId()) {
            return '';
        }

        return 'kat' . $this->getId() . '-' . cleanFileName($title) . '/';
    }

    /**
     * Get parent category.
     *
     * @return ?static
     */
    public function getParent(): ?self
    {
        $cetegory = null;
        if (null !== $this->parentId) {
            $cetegory = app(OrmService::class)->getOne(static::class, $this->parentId);
        }

        return $cetegory;
    }

    /**
     * Get attached categories.
     *
     * @todo natsort when sorted by title
     *
     * @param bool $onlyVisible Only return visible
     *
     * @return self[]
     */
    public function getChildren(bool $onlyVisible = false): array
    {
        $orderBy = 'navn';
        if ($this->hasWeightedChildren()) {
            $orderBy = '`order`, navn';
        }

        $children = app(OrmService::class)->getByQuery(
            self::class,
            '
            SELECT * FROM kat
            WHERE bind = ' . $this->getId() . '
            ORDER BY ' . $orderBy
        );

        if (!$onlyVisible) {
            return $children;
        }

        foreach ($children as $key => $child) {
            if (!$child->isVisible()) {
                unset($children[$key]);
            }
        }

        return array_values($children);
    }

    /**
     * Get children that are suitable for displaying.
     *
     * @return self[]
     */
    public function getVisibleChildren(): array
    {
        return $this->getChildren(true);
    }

    /**
     * Check if it has attached categories.
     *
     * @param bool $onlyVisible Only check visible
     */
    public function hasChildren(bool $onlyVisible = false): bool
    {
        $children = $this->getChildren($onlyVisible);
        if ($children) {
            if ($onlyVisible) {
                $this->visible = true;
            }

            return true;
        }

        return false;
    }

    /**
     * Check if there are children that are appropriate for displaying.
     */
    public function hasVisibleChildren(): bool
    {
        return $this->hasChildren(true);
    }

    /**
     * Return attache pages.
     *
     * @param string $order What column to order by
     *
     * @return Page[]
     */
    public function getPages(string $order = 'navn', bool $reverseOrder = false): array
    {
        app(DbService::class)->addLoadedTable('bind');

        if (!in_array($order, ['navn', 'for', 'pris', 'varenr'], true)) {
            $order = 'navn';
        }

        $pages = app(OrmService::class)->getByQuery(
            Page::class,
            '
            SELECT * FROM sider
            WHERE id IN(SELECT side FROM bind WHERE kat = ' . $this->getId() . ')
            ORDER BY `' . $order . '` ' . ($reverseOrder ? 'DESC' : 'ASC')
        );

        $objectArray = [];
        foreach ($pages as $page) {
            $objectArray[] = [
                'id'     => $page->getId(),
                'navn'   => $page->getTitle(),
                'for'    => $page->getOldPrice(),
                'pris'   => $page->getPrice(),
                'varenr' => $page->getSku(),
                'object' => $page,
            ];
        }
        $objectArray = arrayNatsort($objectArray, $order, $reverseOrder ? 'desc' : '');
        $pages = [];
        foreach ($objectArray as $item) {
            $pages[] = $item['object'];
        }

        return $pages;
    }

    /**
     * Chekc if there are andy attached pages.
     */
    public function hasPages(): bool
    {
        $db = app(DbService::class);

        $db->addLoadedTable('bind');

        $hasPages = (bool) $db->fetchOne('SELECT kat FROM `bind` WHERE `kat` = ' . $this->getId());
        if ($hasPages) {
            $this->visible = true;
        }

        return $hasPages;
    }

    /**
     * Check if there is any content for this category.
     */
    public function hasContent(): bool
    {
        return $this->hasChildren() || $this->hasPages();
    }

    /**
     * Is page currently not placed on the website.
     */
    public function isInactive(): bool
    {
        return (bool) $this->getRoot()->getId();
    }

    /**
     * Find the root category.
     */
    public function getRoot(): self
    {
        return $this->getBranch()[0];
    }

    /**
     * Get the full list of categories leading to the root element.
     *
     * @return self[]
     */
    public function getBranch(): array
    {
        $nodes = [];
        $category = $this;
        do {
            $nodes[] = $category;
        } while ($category = $category->getParent());

        return array_values(array_reverse($nodes));
    }

    /**
     * Get display path.
     */
    public function getPath(): string
    {
        $path = '/';
        foreach ($this->getBranch() as $category) {
            $path .= $category->getTitle() . '/';
        }

        return $path;
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $db = app(DbService::class);

        return [
            'navn'             => $db->quote($this->title),
            'bind'             => null !== $this->parentId ? (string) $this->parentId : 'NULL',
            'icon_id'          => null !== $this->iconId ? (string) $this->iconId : 'NULL',
            'vis'              => (string) $this->renderMode,
            'email'            => $db->quote($this->email),
            'custom_sort_subs' => (string) (int) $this->weightedChildren,
            'order'            => (string) $this->weight,
            'access'           => '""',
        ];
    }
}
