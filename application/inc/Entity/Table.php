<?php

class Table
{
    const TABLE_NAME = 'lists';
    const COLUMN_TYPE_STRING = 0;
    const COLUMN_TYPE_INT = 1;
    const COLUMN_TYPE_PRICE = 2;
    const COLUMN_TYPE_PRICE_NEW = 3;
    const COLUMN_TYPE_PRICE_OLD = 4;

    // Backed by DB
    private $id;
    private $pageId;
    private $title;
    private $columnData;
    private $orderBy;

    // Runtime
    private $columns;

    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setPageId($data['page_id'])
            ->setTitle($data['title'])
            ->setColumnData($data['column_data'])
            ->setOrderBy($data['order_by']);
    }

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
    private function setId(int $id = null): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        if ($this->id === null) {
            $this->save();
        }

        return $this->id;
    }

    private function setPageId(int $pageId): self
    {
        $this->pageId = $pageId;

        return $this;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setColumnData(string $columnData): self
    {
        $this->columnData = $columnData;
        $this->columns = json_decode($columnData, true);

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    private function setOrderBy(int $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getOrderBy(): int
    {
        return $this->orderBy;
    }

    // ORM related functions
    public function getRows()
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

    public function getPage(): Page
    {
        return ORM::getOne(Page::class, $this->pageId);
    }

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
