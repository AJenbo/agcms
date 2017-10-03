<?php namespace AGCMS\Entity;

use AGCMS\ORM;
use AGCMS\Render;

class Table extends AbstractEntity
{
    /**
     * Table name in database.
     */
    const TABLE_NAME = 'lists';
    const COLUMN_TYPE_STRING = 0;
    const COLUMN_TYPE_INT = 1;
    const COLUMN_TYPE_PRICE = 2;
    const COLUMN_TYPE_PRICE_NEW = 3;
    const COLUMN_TYPE_PRICE_OLD = 4;

    // Backed by DB
    /**
     * Parent page id.
     */
    private $pageId;

    /**
     * Table caption.
     */
    private $title;

    /**
     * The default column to order by, starting from 0.
     */
    private $orderBy;

    /**
     * If rows can be linked to pages.
     */
    private $hasLinks;

    // Runtime
    /**
     * Decoded column data.
     */
    private $columns;

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setPageId($data['page_id'])
            ->setTitle($data['title'])
            ->setColumnData($data['column_data'])
            ->setOrderBy($data['order_by'])
            ->setHasLinks($data['has_links']);
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
        $columnSortings = explode('<', $data['sorts']);
        $columnSortings = array_map('intval', $columnSortings);
        $columnTypes = explode('<', $data['cells']);
        $columnTypes = array_map('intval', $columnTypes);
        $columnTitles = explode('<', $data['cell_names']);
        $columnTitles = array_map('html_entity_decode', $columnTitles);

        $columns = [];
        foreach ($columnTitles as $key => $title) {
            $sorting = $columnSortings[$key] ?? 0;
            $options = [];
            if ($sorting) {
                Render::addLoadedTable('tablesort');
                $tablesort = db()->fetchOne("SELECT `text` FROM `tablesort` WHERE id = " . $sorting);
                if ($tablesort) {
                    $options = explode('<', $tablesort['text']);
                    $options = array_map('html_entity_decode', $options);
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
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setColumnData(string $columnData): self
    {
        $this->columns = json_decode($columnData, true);

        return $this;
    }

    /**
     * Get tabel colum structure.
     *
     * @return array
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
     * @return self
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
     * @return self
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
     * Get table rows.
     *
     * @return array
     */
    public function getRows(int $orderBy = null): array
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
            $row['link'] = $this->hasLinks() ? (int) $row['link'] : 0;
            $cells = explode('<', $row['cells']);
            $cells = array_map('html_entity_decode', $cells);
            unset($row['cells'], $row['list_id']);

            foreach ($this->columns as $key => $column) {
                $row[$key] = $cells[$key] ?? '';
                if (!empty($column['type'])) {
                    $row[$key] = (int) $row[$key];
                }
            }
        }
        unset($row);

        return $this->orderRows($rows, $orderBy);
    }

    /**
     * Sort a 2D array based on a custome sort order.
     *
     * @param array  $rows    Array to sort
     * @param string $orderBy Key to sort by
     *
     * @return array
     */
    private function orderRows(array $rows, int $orderBy = null): array
    {
        $orderBy = $orderBy ?? $this->orderBy;
        $orderBy = max($orderBy, 0);
        $orderBy = min($orderBy, count($this->columns) - 1);

        if (!$this->columns[$orderBy]['sorting']) {
            return arrayNatsort($rows, 'id', $orderBy); // Alpha numeric
        }

        $options = $this->columns[$orderBy]['options'];

        $arySort = [];
        foreach ($rows as $aryRow) {
            $arySort[$aryRow[$strIndex]] = -1;
            foreach ($options as $kalKey => $kalSort) {
                if ($aryRow[$orderBy] == $kalSort) {
                    $arySort[$aryRow[$strIndex]] = $kalKey;
                    break;
                }
            }
        }

        natcasesort($arySort);

        $aryResult = [];
        foreach (array_keys($arySort) as $arySortKey) {
            foreach ($rows as $aryRow) {
                if ($aryRow[$strIndex] == $arySortKey) {
                    $aryResult[] = $aryRow;
                    break;
                }
            }
        }

        return $aryResult;
    }

    /**
     * Get the page this table belongs to.
     *
     * @return \Page
     */
    public function getPage(): Page
    {
        return ORM::getOne(Page::class, $this->pageId);
    }

    /**
     * Get data in array format for the database.
     *
     * @return array
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

        return [
            'page_id'    => $this->pageId,
            'title'      => db()->eandq($this->title),
            'sorts'      => db()->eandq($columnSortings),
            'cells'      => db()->eandq($columnTypes),
            'cell_names' => db()->eandq($columnTitles),
            'sort'       => $this->orderBy,
            'link'       => $this->hasLinks ? 1 : 0,
        ];
    }
}
