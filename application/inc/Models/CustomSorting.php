<?php namespace App\Models;

use App\Services\DbService;

class CustomSorting extends AbstractEntity
{
    /**  Table name in database. */
    const TABLE_NAME = 'tablesort';

    // Backed by DB

    /** @var string Title */
    private $title = '';

    /** @var string[] Ordered list of values. */
    private $items = [];

    public function __construct(array $data = [])
    {
        $this->setTitle($data['title'])
            ->setItems($data['items'] ?? [])
            ->setId($data['id'] ?? null);
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set items.
     *
     * @param string[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self
    {
        $items = array_filter($items);
        $this->items = $items;

        return $this;
    }

    /**
     * Get items.
     *
     * @return string[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public static function mapFromDB(array $data): array
    {
        $items = explode('<', $data['text']);
        $items = array_map('html_entity_decode', $items);

        return [
            'id'     => $data['id'],
            'title'  => $data['navn'],
            'items'  => $items,
        ];
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $items = array_map('htmlspecialchars', $this->items);
        $items = implode('<', $items);

        /** @var DbService */
        $db = app(DbService::class);

        return [
            'navn'  => $db->quote($this->title),
            'text'  => $db->quote($items),
        ];
    }
}
