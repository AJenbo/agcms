<?php namespace App\Models;

use App\Exceptions\Exception;
use App\Services\DbService;
use App\Services\OrmService;

class Table extends AbstractEntity
{
    /** Table name in database. */
    const TABLE_NAME = 'lists';

    /** Cell string */
    const COLUMN_TYPE_STRING = 0;

    /** Cell integer */
    const COLUMN_TYPE_INT = 1;

    /** Cell price */
    const COLUMN_TYPE_PRICE = 2;

    /** Cell sales price */
    const COLUMN_TYPE_PRICE_NEW = 3;

    /** Cell previous price */
    const COLUMN_TYPE_PRICE_OLD = 4;

    // Backed by DB

    /** @var int Parent page id. */
    private $pageId;

    /** @var string Table caption. */
    private $title = '';

    /** @var int The default column to order by, starting from 0. */
    private $orderBy = 0;

    /** @var bool If rows can be linked to pages. */
    private $hasLinks = false;

    /** @var bool Indicate if there is a column with sales prices. */
    private $hasPrices = false;

    // Runtime

    /** @var array[] Decoded column data. */
    private $columns = [];

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data = [])
    {
        $this->setPageId($data['page_id'])
            ->setTitle($data['title'])
            ->setColumnData($data['column_data'])
            ->setOrderBy($data['order_by'])
            ->setHasLinks($data['has_links'])
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
        $columnSortings = explode('<', $data['sorts']);
        $columnSortings = array_map('intval', $columnSortings);
        $columnTypes = explode('<', $data['cells']);
        $columnTypes = array_map('intval', $columnTypes);
        $columnTitles = explode('<', $data['cell_names']);
        $columnTitles = array_map('html_entity_decode', $columnTitles);

        /** @var OrmService */
        $orm = app(OrmService::class);

        $columns = [];
        foreach ($columnTitles as $key => $title) {
            $sorting = $columnSortings[$key] ?? 0;
            $options = [];
            if ($sorting) {
                /** @var ?CustomSorting */
                $tablesort = $orm->getOne(CustomSorting::class, $sorting);
                if ($tablesort) {
                    $options = $tablesort->getItems();
                }
            }

            $columns[] = [
                'title'   => $title,
                'type'    => $columnTypes[$key] ?? 0,
                'sorting' => $sorting,
                'options' => $options,
            ];
        }
        $columns = json_encode($columns);

        return [
            'id'          => $data['id'],
            'page_id'     => $data['page_id'],
            'title'       => $data['title'],
            'column_data' => $columns,
            'order_by'    => $data['sort'],
            'has_links'   => (bool) $data['link'],
        ];
    }

    // Getters and setters

    /**
     * Set parent page id.
     *
     * @param int $pageId The page the table belongs on
     *
     * @return $this
     */
    private function setPageId(int $pageId): self
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get page id.
     *
     * @return int
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * Set the table caption.
     *
     * @param string $title The caption
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the table caption.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the column data.
     *
     * @param string $columnData Array encoded as JSON
     *
     * @return $this
     */
    public function setColumnData(string $columnData): self
    {
        $this->columns = json_decode($columnData, true);

        foreach ($this->columns as $column) {
            if (in_array($column['type'], [self::COLUMN_TYPE_PRICE, self::COLUMN_TYPE_PRICE_NEW], true)) {
                $this->hasPrices = true;
                break;
            }
        }

        return $this;
    }

    /**
     * Get tabel colum structure.
     *
     * @return array[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set the default sorting column.
     *
     * @param int $orderBy First column = 0
     *
     * @return $this
     */
    private function setOrderBy(int $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Get the default sort by column (zero index).
     *
     * @return int
     */
    public function getOrderBy(): int
    {
        return $this->orderBy;
    }

    /**
     * Allow rows to link to pages.
     *
     * @param bool $hasLinks
     *
     * @return $this
     */
    private function setHasLinks(bool $hasLinks): self
    {
        $this->hasLinks = $hasLinks;

        return $this;
    }

    /**
     * Allow rows to link to pages.
     *
     * @return bool
     */
    public function hasLinks(): bool
    {
        return $this->hasLinks;
    }

    // ORM related functions

    /**
     * Indicate if there is a column with sales prices.
     *
     * @return bool
     */
    public function hasPrices(): bool
    {
        return $this->hasPrices;
    }

    /**
     * Get table rows.
     *
     * @return array
     */
    public function getRows(int $orderBy = null): array
    {
        /** @var DbService */
        $db = app(DbService::class);

        $rows = $db->fetchArray(
            '
            SELECT *
            FROM `list_rows`
            WHERE `list_id` = ' . $this->getId()
        );
        $db->addLoadedTable('list_rows');

        /** @var OrmService */
        $orm = app(OrmService::class);

        // Cells are indexed by id, this is needed for sorting the rows
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['page'] = null;
            if ($this->hasLinks() && $row['link']) {
                $row['page'] = $orm->getOne(Page::class, $row['link']);
            }
            $cells = explode('<', $row['cells']);
            $cells = array_map('html_entity_decode', $cells);
            unset($row['cells'], $row['list_id'], $row['link']);

            foreach ($this->columns as $key => $column) {
                $row[$key] = $cells[$key] ?? '';
                if (!empty($column['type'])) {
                    $row[$key] = (int) $row[$key];
                }
            }
        }

        return $this->orderRows($rows, $orderBy);
    }

    /**
     * Add a new row to the table.
     *
     * @param array    $cells
     * @param int|null $link
     *
     * @return int Id of the new row
     */
    public function addRow(array $cells, int $link = null): int
    {
        $cells = array_map('htmlspecialchars', $cells);
        $cells = implode('<', $cells);

        /** @var DbService */
        $db = app(DbService::class);

        return $db->query(
            '
            INSERT INTO `list_rows`(`list_id`, `cells`, `link`)
            VALUES (' . $this->getId() . ', ' . $db->quote($cells) . ', ' . (null === $link ? 'NULL' : $link) . ')
            '
        );
    }

    /**
     * Update an existing row.
     *
     * @param int      $rowId
     * @param array    $cells
     * @param int|null $link
     *
     * @return void
     */
    public function updateRow(int $rowId, array $cells, int $link = null): void
    {
        $cells = array_map('htmlspecialchars', $cells);
        $cells = implode('<', $cells);

        /** @var DbService */
        $db = app(DbService::class);

        $db->query(
            '
            UPDATE `list_rows` SET
                `cells` = ' . $db->quote($cells) . ',
                `link` = ' . (null === $link ? 'NULL' : $link) . '
            WHERE list_id = ' . $this->getId() . '
              AND id = ' . $rowId
        );
    }

    /**
     * Remove a row from the table.
     *
     * @param int $rowId
     *
     * @return void
     */
    public function removeRow(int $rowId): void
    {
        /** @var DbService */
        $db = app(DbService::class);

        $db->query('DELETE FROM `list_rows` WHERE list_id = ' . $this->id . ' AND `id` = ' . $rowId);
    }

    /**
     * Sort a 2D array based on a custome sort order.
     *
     * @param array[] $rows
     *
     * @return array[]
     */
    private function orderRows(array $rows, int $orderBy = null): array
    {
        $orderBy = $orderBy ?? $this->orderBy;
        $orderBy = max($orderBy, 0);
        $orderBy = min($orderBy, count($this->columns) - 1);

        if (!$this->columns[$orderBy]['sorting']) {
            return arrayNatsort($rows, (string) $orderBy); // Alpha numeric
        }

        $options = $this->columns[$orderBy]['options'];
        $options = array_flip($options);

        $tempArray = [];
        foreach ($rows as $rowKey => $row) {
            $tempArray[$rowKey] = $options[$row[$orderBy]] ?? -1;
        }

        asort($tempArray);

        $result = [];
        foreach (array_keys($tempArray) as $rowKey) {
            $result[] = $rows[$rowKey];
        }

        return $result;
    }

    /**
     * Get the page this table belongs to.
     *
     * @throws Exception
     *
     * @return Page
     */
    public function getPage(): Page
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Page */
        $page = $orm->getOne(Page::class, $this->pageId);
        if (!$page) {
            throw new Exception(_('Page not found.'));
        }

        return $page;
    }

    /**
     * Get data in array format for the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
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

        /** @var DbService */
        $db = app(DbService::class);

        return [
            'page_id'    => (string) $this->pageId,
            'title'      => $db->quote($this->title),
            'sorts'      => $db->quote($columnSortings),
            'cells'      => $db->quote($columnTypes),
            'cell_names' => $db->quote($columnTitles),
            'sort'       => (string) $this->orderBy,
            'link'       => (string) (int) $this->hasLinks,
        ];
    }
}
