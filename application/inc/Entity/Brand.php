<?php namespace AGCMS\Entity;

class Brand extends AbstractRenderable
{
    use HasIcon;

    /** Table name in database. */
    const TABLE_NAME = 'maerke';

    // Backed by DB

    /** @var string The external link for this brand. */
    private $link = '';

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data = [])
    {
        $this->iconId = $data['icon_id'];
        $this->setLink($data['link'])
            ->setTitle($data['title'])
            ->setId($data['id'] ?? null);
    }

    /**
     * Map data from DB table to entity.
     *
     * @param array $data The data from the database
     *
     * @return array
     */
    public static function mapFromDB(array $data): array
    {
        return [
            'id'      => $data['id'],
            'title'   => $data['navn'],
            'link'    => $data['link'],
            'icon_id' => $data['icon_id'],
        ];
    }

    // Getters and setters

    /**
     * Set external url link.
     *
     * @param string $link The url
     *
     * @return $this
     */
    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get the external link for this brand.
     *
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    // General methods

    /**
     * Get the url slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return 'mærke' . $this->getId() . '-' . clearFileName($this->getTitle()) . '/';
    }

    /**
     * Check if the brand has any active pages associated.
     *
     * @return bool
     */
    public function hasPages(): bool
    {
        /** @var Page[] */
        $pages = app('orm')->getByQuery(Page::class, 'SELECT * FROM sider WHERE maerke = ' . $this->getId());

        foreach ($pages as $page) {
            if (!$page->isInactive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all pages under this brand.
     *
     * @param string $order How to order the pages
     *
     * @return Page[]
     */
    public function getPages(string $order = 'navn'): array
    {
        if (!in_array($order, ['navn', 'for', 'pris', 'varenr'], true)) {
            $order = 'navn';
        }

        /** @var Page[] */
        $pages = app('orm')->getByQuery(
            Page::class,
            'SELECT * FROM sider WHERE maerke = ' . $this->getId() . ' ORDER BY sider.`' . $order . '` ASC'
        );

        $objectArray = [];
        foreach ($pages as $page) {
            if ($page->isInactive()) {
                continue;
            }

            $objectArray[] = [
                'id'     => $page->getId(),
                'navn'   => $page->getTitle(),
                'for'    => $page->getOldPrice(),
                'pris'   => $page->getPrice(),
                'varenr' => $page->getSku(),
                'object' => $page,
            ];
        }
        $objectArray = arrayNatsort($objectArray, $order);
        $pages = [];
        foreach ($objectArray as $item) {
            $pages[] = $item['object'];
        }

        return $pages;
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
            'navn'    => app('db')->quote($this->title),
            'link'    => app('db')->quote($this->link),
            'icon_id' => null !== $this->iconId ? (string) $this->iconId : 'NULL',
        ];
    }
}
