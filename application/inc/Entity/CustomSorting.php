<?php namespace AGCMS\Entity;

class CustomSorting extends AbstractEntity
{
    /**  Table name in database. */
    const TABLE_NAME = 'tablesort';

    // Backed by DB
    /** @var string Title */
    private $title = '';
    /** @var string[] Ordered list of values. */
    private $items = [];

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
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

    /**
     * Map data from DB table to entity.
     *
     * @param array $data The data from the database
     *
     * @return array
     */
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

    /**
     * Get data in array format for the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
    {
        $items = array_map('htmlspecialchars', $this->items);
        $items = implode('<', $items);

        return [
            'navn'  => db()->eandq($this->title),
            'text'  => db()->eandq($items),
        ];
    }
}
