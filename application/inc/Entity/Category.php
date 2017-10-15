<?php namespace AGCMS\Entity;

use Exception;
use AGCMS\ORM;
use AGCMS\Render;

class Category extends AbstractRenderable
{
    /**
     * Table name in database.
     */
    const TABLE_NAME = 'kat';

    /**
     * Do not show category.
     */
    const HIDDEN = 0;

    /**
     * Gallery rendering of pages.
     */
    const GALLERY = 1;

    /**
     * List rendering of pages.
     */
    const LIST = 2;

    // Backed by DB
    /**
     * Parent id.
     */
    private $parentId;

    /**
     * Icon file path.
     */
    private $iconPath;

    /**
     * Render mode for page list.
     */
    private $renderMode;

    /**
     * Contact email.
     */
    private $email;

    /**
     * Are children to be fetched by weight.
     */
    private $weightedChildren;

    /**
     * Sorting weight.
     */
    private $weight;

    // Runtime
    /**
     * Cache if category is visible or not.
     */
    private $visable;

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTitle($data['title'])
            ->setParentId($data['parent_id'])
            ->setIconPath($data['icon_path'])
            ->setRenderMode($data['render_mode'])
            ->setEmail($data['email'])
            ->setWeightedChildren($data['weighted_children'])
            ->setWeight($data['weight']);
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
            'id'                => $data['id'],
            'title'             => $data['navn'],
            'parent_id'         => $data['bind'],
            'icon_path'         => $data['icon'],
            'render_mode'       => $data['vis'],
            'email'             => $data['email'],
            'weighted_children' => $data['custom_sort_subs'],
            'weight'            => $data['order'],
        ];
    }

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

    // Getters and setters

    /**
     * Set parent id.
     *
     * @return self
     */
    public function setParentId(int $parentId = null): self
    {
        if ($parentId !== null && $this->id !== null && $this->id <= 0) {
            throw new Exception(_('Your not allowed to move root categories'));
        }

        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Set icon file path.
     */
    public function setIconPath(string $iconPath = null): self
    {
        $this->iconPath = $iconPath;

        return $this;
    }

    /**
     * Set render mode.
     *
     * @param int $renderMode The render mode for displaying pages
     *
     * @return self
     */
    public function setRenderMode(int $renderMode): self
    {
        $this->renderMode = $renderMode;

        return $this;
    }

    /**
     * Get the page list rendermode for this category.
     *
     * @return int
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
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the contact email address for pages in this category.
     *
     * @return string
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
     * @return self
     */
    public function setWeightedChildren(bool $weightedChildren): self
    {
        $this->weightedChildren = (int) $weightedChildren;

        return $this;
    }

    /**
     * Are the children of this category be manually ordered.
     *
     * @return bool
     */
    public function hasWeightedChildren(): bool
    {
        return (bool) $this->weightedChildren;
    }

    /**
     * Set weight.
     *
     * @param int $weight Order-by weight
     *
     * @return self
     */
    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    // General methodes

    /**
     * Should the category be visible on the website (is it empty or hidden).
     *
     * @return bool
     */
    public function isVisable(): bool
    {
        if (self::HIDDEN === $this->renderMode) {
            return false;
        }

        if (null === $this->visable) {
            if ($this->hasPages() || $this->hasVisibleChildren()) {
                return true;
            }

            $this->visable = false;
        }

        return $this->visable;
    }

    /**
     * Get the url slug.
     *
     * @return string
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

        return 'kat' . $this->getId() . '-' . clearFileName($title) . '/';
    }

    /**
     * Get parent category.
     */
    public function getParent(): ?self
    {
        if (null === $this->parentId) {
            return null;
        }

        return ORM::getOne(self::class, $this->parentId);
    }

    /**
     * Get attached categories.
     *
     * @todo natsort when sorted by title
     *
     * @param bool $onlyVisable Only return visible
     *
     * @return Category[]
     */
    public function getChildren(bool $onlyVisable = false): array
    {
        $orderBy = 'navn';
        if ($this->hasWeightedChildren()) {
            $orderBy = '`order`, navn';
        }

        $children = ORM::getByQuery(
            self::class,
            '
            SELECT * FROM kat
            WHERE bind = ' . $this->getId() . '
            ORDER BY ' . $orderBy
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

    /**
     * @return Category[]
     */
    public function getVisibleChildren(): array
    {
        return $this->getChildren(true);
    }

    /**
     * Check if it has attached categories.
     *
     * @param bool $onlyVisable Only check visible
     *
     * @return bool
     */
    public function hasChildren(bool $onlyVisable = false): bool
    {
        $children = $this->getChildren($onlyVisable);
        if ($children) {
            if ($onlyVisable) {
                $this->visable = true;
            }

            return true;
        }

        return false;
    }

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
        Render::addLoadedTable('bind');

        if (!in_array($order, ['navn', 'for', 'pris', 'varenr'])) {
            $sort = 'navn';
        }

        $pages = ORM::getByQuery(
            Page::class,
            '
            SELECT * FROM sider
            WHERE id IN(SELECT side FROM bind WHERE kat = ' . $this->getId() . ')
            ORDER BY `' . db()->esc($order) . '` ' . ($reverseOrder ? 'DESC' : 'ASC')
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
        $objectArray = arrayNatsort($objectArray, 'id', $order, $reverseOrder ? 'desc' : '');
        $pages = [];
        foreach ($objectArray as $item) {
            $pages[] = $item['object'];
        }

        return $pages;
    }

    public function hasPages(): bool
    {
        Render::addLoadedTable('bind');

        $hasPages = (bool) db()->fetchOne('SELECT kat FROM `bind` WHERE `kat` = ' . $this->getId());
        if ($hasPages) {
            $this->visable = true;
        }

        return $hasPages;
    }

    public function hasContent(): bool
    {
        return $this->hasChildren() || $this->hasPages();
    }

    /**
     * Is page currently not placed on the website.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return (bool) $this->getRoot()->getId();
    }

    public function getRoot(): self
    {
        return $this->getBranch()[0];
    }

    /**
     * Get the full list of categories leading to the root element.
     *
     * @return Category[]
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

    public function getPath(): string
    {
        $path = '/';
        foreach ($this->getBranch() as $category) {
            $path .= $category->getTitle() . '/';
        }

        return $path;
    }

    /**
     * Get the file that is being used as an icon.
     */
    public function getIcon(): ?File
    {
        if (null === $this->iconPath) {
            return null;
        }

        return File::getByPath($this->iconPath);
    }

    // ORM related functions

    /**
     * Get data in array format for the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
    {
        return [
            'navn'             => db()->eandq($this->title),
            'bind'             => null !== $this->parentId ? (string) $this->parentId : 'NULL',
            'icon'             => null !== $this->iconPath ? db()->eandq($this->iconPath) : 'NULL',
            'vis'              => (string) $this->renderMode,
            'email'            => db()->eandq($this->email),
            'custom_sort_subs' => (string) $this->weightedChildren,
            'order'            => (string) $this->weight,
        ];
    }
}
