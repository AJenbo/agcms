<?php namespace AGCMS\Entity;

use AGCMS\ORM;
use AGCMS\Render;

/**
 * Category class.
 */
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
     * @param int $parentId Id of parent category
     *
     * @return self
     */
    public function setParentId(int $parentId = null): self
    {
        if ($this->id && $this->id > 0) {
            $this->parentId = $parentId;
        }

        return $this;
    }

    /**
     * Get the parent category id.
     *
     * @return int
     */
    private function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * Set icon file path.
     *
     * @param string $iconPath Icon file path
     *
     * @return self
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

    /**
     * Get category sorting weight.
     *
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    // General methodes

    /**
     * Should the category be visible on the website (is it empty or hidden).
     *
     * @return bool
     */
    public function isVisable(): bool
    {
        if ($this->renderMode === self::HIDDEN) {
            return false;
        }

        if ($this->visable === null) {
            Render::addLoadedTable('bind');
            if (db()->fetchOne("SELECT kat FROM `bind` WHERE `kat` = " . $this->getId())) {
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
        if ($this->parentId !== null) {
            return ORM::getOne(self::class, $this->parentId);
        }

        return null;
    }

    /**
     * Get attached categories.
     *
     * @param bool $onlyVisable Only return visible
     *
     * @return array
     */
    public function getChildren(bool $onlyVisable = false): array
    {
        $orderBy = 'navn';
        if ($this->hasWeightedChildren()) {
            $orderBy = '`order`, navn';
        }

        $children = ORM::getByQuery(
            self::class,
            "
            SELECT * FROM kat
            WHERE bind = " . $this->getId() . "
            ORDER BY " . $orderBy
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
        $children = $this->getChildren();
        if (!$onlyVisable) {
            return (bool) $children;
        }

        foreach ($children as $child) {
            if ($child->isVisable()) {
                $this->visable = true;

                return true;
            }
        }

        $this->visable = false;

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
     * @return array
     */
    public function getPages(string $order = 'navn', bool $reverseOrder = false): array
    {
        Render::addLoadedTable('bind');

        if (!in_array($order, ['navn', 'for', 'pris', 'varenr'])) {
            $sort = 'navn';
        }

        return ORM::getByQuery(
            Page::class,
            "
            SELECT * FROM sider
            WHERE id IN(SELECT side FROM bind WHERE kat = " . $this->getId() . ")
            ORDER BY `" . db()->esc($order) . "` " . ($reverseOrder ? "DESC" : "ASC")
        );

        $objectArray = [];
        foreach ($pages as $page) {
            $objectArray[] = [
                'id' => $page->getId(),
                'navn' => $page->getTitle(),
                'for' => $page->getOldPrice(),
                'pris' => $page->getPrice(),
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
     * @return array
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
        if (!$this->iconPath) {
            return null;
        }

        return File::getByPath($this->iconPath);
    }

    // ORM related functions

    /**
     * Get data in array format for the database.
     *
     * @return array
     */
    public function getDbArray(): array
    {
        return [
            'navn'             => db()->eandq($this->title),
            'bind'             => $this->parentId !== null ? $this->parentId : 'NULL',
            'icon'             => $this->getIcon() ? db()->eandq($this->getIcon()->getPath()) : 'NULL',
            'vis'              => $this->renderMode,
            'email'            => db()->eandq($this->email),
            'custom_sort_subs' => $this->weightedChildren,
            'order'            => $this->weight,
        ];
    }
}
