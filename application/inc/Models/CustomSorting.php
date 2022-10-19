<?php

namespace App\Models;

use App\Services\DbService;

class CustomSorting extends AbstractEntity
{
    /**  Table name in database. */
    public const TABLE_NAME = 'tablesort';

    // Backed by DB

    /** @var string Title */
    private string $title = '';

    /** @var string[] Ordered list of values. */
    private array $items = [];

    public function __construct(array $data = [])
    {
        $items = $data['items'] ?? null;
        if (!is_array($items)) {
            $items = [];
        }

        $this->setTitle(strval($data['title']))
            ->setItems($items)
            ->setId(intOrNull($data['id'] ?? null));
    }

    /**
     * Set title.
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

        $db = app(DbService::class);

        return [
            'navn'  => $db->quote($this->title),
            'text'  => $db->quote($items),
        ];
    }
}
