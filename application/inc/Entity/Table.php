<?php

class Table extends AbstractEntity
{
    /**
     * Table name in database
     */
    const TABLE_NAME = 'lists';
    const COLUMN_TYPE_STRING = 0;
    const COLUMN_TYPE_INT = 1;
    const COLUMN_TYPE_PRICE = 2;
    const COLUMN_TYPE_PRICE_NEW = 3;
    const COLUMN_TYPE_PRICE_OLD = 4;

    // Backed by DB
    /**
     * Parent page id
     */
    private $pageId;

    /**
     * Table caption
     */
    private $title;

    /**
     * Column data as JSON
     */
    private $columnData;

    /**
     * The default column to order by, starting from 0
     */
    private $orderBy;

    // Runtime
    /**
     * Decoded column data
     */
    private $columns;

    /**
     * Construct the entity
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setPageId($data['page_id'])
            ->setTitle($data['title'])
            ->setColumnData($data['column_data'])
            ->setOrderBy($data['order_by']);
    }

    /**
     * Map data from DB table to entity
     *
     * @param array The data from the database
     *
     * @return array
     */
    public static function mapFromDB(array $data): array
    {
        $columnSortings = explode('<', $data['sorts']);
        $columnSortings = array_map('intval', $columnSortings);
        $columnTypes = explode('<', $data['cells']);
        $columnTypes = array_map('intval', $columnTypes);
        $columnTitles = explode('<', $data['cell_names']);
        $columnTitles = array_map('html_entity_decode', $columnTitles);

        $columns = [];
        foreach ($columnTitles as $key => $title) {
            $columns[] = [
                'title'   => $title,
                'type'    => $columnTypes[$key] ?? 0,
                'sorting' => $columnSortings[$key] ?? 0,
            ];
        }
        $columns = json_encode($columns);

        return [
            'id'          => $data['id'],
            'page_id'     => $data['page_id'],
            'title'       => $data['title'],
            'column_data' => $columns,
            'order_by'    => $data['sort'],
        ];
    }

    // Getters and setters
    /**
     * Set parent page id
     *
     * @param int $pageId The page the table belongs on
     *
     * @return self
     */
    private function setPageId(int $pageId): self
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get page id
     *
     * @return int
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * Set the table caption
     *
     * @param string $title The caption
     *
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the table caption
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the column data
     *
     * @param string $columnData Array encoded as JSON
     *
     * @return self
     */
    public function setColumnData(string $columnData): self
    {
        $this->columnData = $columnData;
        $this->columns = json_decode($columnData, true);

        return $this;
    }

    /**
     * Get tabel colum structure
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set the default sorting column
     *
     * @param int $orderBy First column = 0
     *
     * @return self
     */
    private function setOrderBy(int $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Get the default sort by column (zero index)
     *
     * @return int
     */
    public function getOrderBy(): int
    {
        return $this->orderBy;
    }

    // ORM related functions
    /**
     * Get table rows
     *
     * @return array
     */
    public function getRows(): array
    {
        $rows = db()->fetchArray(
            "
            SELECT *
            FROM `list_rows`
            WHERE `list_id` = " . $this->getId()
        );
        Render::addLoadedTable('list_rows');

        // Cells are indexed by id, this is needed for sorting the rows
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['link'] = (int) $row['link'];
            $cells = explode('<', $row['cells']);
            $cells = array_map('html_entity_decode', $cells);
            unset($row['cells']);
            unset($row['list_id']);
            foreach ($this->columns as $key => $column) {
                $row[$key] = $cells[$key] ?? '';
                if (!empty($column['type'])) {
                    $row[$key] = (int) $row[$key];
                }
            }
        }

        unset($row);

        return $rows;
    }

    /**
     * Get the page this table belongs to
     *
     * @return \Page
     */
    public function getPage(): Page
    {
        return ORM::getOne(Page::class, $this->pageId);
    }

    /**
     * Save entity to database
     */
    public function save()
    {
        $columnSortings = [];
        $columnTypes = [];
        $columnTitles = [];
        foreach ($this->columns as $column) {
            $columnSortings[] = $column['sorting'];
            $columnTypes[] = $column['type'];
            $columnTitles[] = $column['title'];
        }

        $columnSortings = implode('<', $columnSortings);
        $columnTypes = implode('<', $columnTypes);
        $columnTitles = array_map('htmlspecialchars', $columnTitles);
        $columnTitles = implode('<', $columnTitles);

        if ($this->id === null) {
            db()->query(
                "
                INSERT INTO `" . self::TABLE_NAME . "` (
                    `page_id`,
                    `title`,
                    `sorts`,
                    `cells`,
                    `cell_names`,
                    `sort`
                ) VALUES (
                    " . $this->pageId . ",
                    '" . db()->esc($this->title) . "',
                    '" . db()->esc($columnSortings) . "',
                    '" . db()->esc($columnTypes) . "',
                    '" . db()->esc($columnTitles) . "',
                    " . $this->orderBy
                . ")"
            );
            $this->setId(db()->insert_id);
        } else {
            db()->query(
                "
                UPDATE `" . self::TABLE_NAME . "` SET
                    `page_id` = " . $this->pageId . ",
                    `title` = '" . db()->esc($this->title) . "',
                    `sorts` = '" . db()->esc($columnSortings) . "',
                    `cells` = '" . db()->esc($columnTypes) . "',
                    `cell_names` = '" . db()->esc($columnTitles) . "',
                    `sort` = " . $this->orderBy
                . " WHERE `id` = " . $this->id
            );
        }
        Render::addLoadedTable(self::TABLE_NAME);
    }
}
