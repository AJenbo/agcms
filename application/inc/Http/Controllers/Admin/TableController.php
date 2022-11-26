<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\CustomSorting;
use App\Models\Page;
use App\Models\Table;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TableController extends AbstractAdminController
{
    /**
     * Add table to page.
     */
    public function create(Request $request): JsonResponse
    {
        $table = new Table([
            'page_id'     => $request->request->getInt('page_id'),
            'title'       => $request->getRequestString('title'),
            'column_data' => json_encode($request->request->get('columns', []), JSON_THROW_ON_ERROR),
            'order_by'    => $request->request->getInt('order_by'),
            'has_links'   => $request->request->getBoolean('has_links'),
        ]);
        $table->save();

        return new JsonResponse([]);
    }

    /**
     * Add table to page.
     */
    public function createDialog(Request $request, int $pageId): Response
    {
        return $this->render(
            'admin/addlist',
            [
                'customSortings' => app(OrmService::class)->getByQuery(CustomSorting::class, 'SELECT * FROM `tablesort`'),
                'page_id'        => $pageId,
            ]
        );
    }

    /**
     * Add row to a table.
     */
    public function addRow(Request $request, int $tableId): JsonResponse
    {
        $cells = $request->request->get('cells');
        if (!is_array($cells)) {
            $cells = [];
        }
        $link = $request->getRequestInt('link');
        if ($link) {
            $page = app(OrmService::class)->getOne(Page::class, $link);
            if (!$page) {
                throw new InvalidInput(_('Linked page not found.'), Response::HTTP_NOT_FOUND);
            }
        }

        $table = app(OrmService::class)->getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found.'), Response::HTTP_NOT_FOUND);
        }

        $rowId = $table->addRow($cells, $link);

        return new JsonResponse(['listid' => $tableId, 'rowid' => $rowId]);
    }

    public function updateRow(Request $request, int $tableId, int $rowId): JsonResponse
    {
        $cells = $request->request->get('cells');
        if (!is_array($cells)) {
            $cells = [];
        }
        $link = $request->getRequestInt('link');
        if ($link) {
            $page = app(OrmService::class)->getOne(Page::class, $link);
            if (!$page) {
                throw new InvalidInput(_('Linked page not found.'), Response::HTTP_NOT_FOUND);
            }
        }

        $table = app(OrmService::class)->getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found.'), Response::HTTP_NOT_FOUND);
        }

        $table->updateRow($rowId, $cells, $link);

        return new JsonResponse(['listid' => $tableId, 'rowid' => $rowId]);
    }

    public function removeRow(Request $request, int $tableId, int $rowId): JsonResponse
    {
        $table = app(OrmService::class)->getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found.'), Response::HTTP_NOT_FOUND);
        }

        $table->removeRow($rowId);

        return new JsonResponse(['listid' => $tableId, 'rowid' => $rowId]);
    }
}
