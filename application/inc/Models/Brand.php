<?php

namespace App\Models;

use App\Services\DbService;
use App\Services\OrmService;

class Brand extends AbstractRenderable
{
    use HasIcon;

    /** Table name in database. */
    public const TABLE_NAME = 'maerke';

    // Backed by DB

    /** @var string The external link for this brand. */
    private string $link = '';

    public function __construct(array $data = [])
    {
        $this->iconId = intOrNull($data['icon_id']);
        $this->setLink(valstring($data['link']))
            ->setTitle(valstring($data['title']))
            ->setId(intOrNull($data['id'] ?? null));
    }

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
     */
    public function getLink(): string
    {
        return $this->link;
    }

    // General methods

    /**
     * Get the url slug.
     */
    public function getSlug(): string
    {
        return 'mærke' . $this->getId() . '-' . cleanFileName($this->getTitle()) . '/';
    }

    /**
     * Check if the brand has any active pages associated.
     */
    public function hasPages(): bool
    {
        $pages = app(OrmService::class)->getByQuery(Page::class, 'SELECT * FROM sider WHERE maerke = ' . $this->getId());

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

        $pages = app(OrmService::class)->getByQuery(
            Page::class,
            'SELECT * FROM sider WHERE maerke = ' . $this->getId() . ' ORDER BY sider.`' . $order . '` ASC'
        );

        $pageMap = [];
        $objectArray = [];
        foreach ($pages as $page) {
            if ($page->isInactive()) {
                continue;
            }

            $pageMap[$page->getId()] = $page;
            $objectArray[] = [
                'id'     => $page->getId(),
                'navn'   => $page->getTitle(),
                'for'    => $page->getOldPrice(),
                'pris'   => $page->getPrice(),
                'varenr' => $page->getSku(),
            ];
        }
        $objectArray = arrayNatsort($objectArray, $order);

        $pages = [];
        foreach ($objectArray as $item) {
            $pages[] = $pageMap[$item['id']];
        }

        return $pages;
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $db = app(DbService::class);

        return [
            'navn'    => $db->quote($this->title),
            'link'    => $db->quote($this->link),
            'icon_id' => null !== $this->iconId ? (string)$this->iconId : 'NULL',
        ];
    }
}
