<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\CustomSorting;
use AGCMS\Entity\Table;
use AGCMS\Exceptions\InvalidInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TableController extends AbstractAdminController
{
    /**
     * Add table to page.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $table = new Table([
            'page_id'     => $request->request->getInt('page_id'),
            'title'       => $request->request->get('title'),
            'column_data' => json_encode($request->request->get('columns', [])),
            'order_by'    => $request->request->getInt('order_by'),
            'has_links'   => $request->request->getBoolean('has_links'),
        ]);
        $table->save();

        return new JsonResponse([]);
    }

    /**
     * Add table to page.
     *
     * @param Request $request
     * @param int     $pageId
     *
     * @return Response
     */
    public function createDialog(Request $request, int $pageId): Response
    {
        return $this->render(
            'admin/addlist',
            [
                'customSortings' => app('orm')->getByQuery(CustomSorting::class, 'SELECT * FROM `tablesort`'),
                'page_id'        => $pageId,
            ]
        );
    }

    /**
     * Add row to a table.
     *
     * @param Request $request
     * @param int     $tableId
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function addRow(Request $request, int $tableId): JsonResponse
    {
        $cells = $request->request->get('cells', []);
        $link = $request->request->get('link');

        /** @var ?Table */
        $table = app('orm')->getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found.'), Response::HTTP_NOT_FOUND);
        }

        $rowId = $table->addRow($cells, $link);

        return new JsonResponse(['listid' => $tableId, 'rowid' => $rowId]);
    }

    /**
     * Update a row in a table.
     *
     * @param Request $request
     * @param int     $tableId
     * @param int     $rowId
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function updateRow(Request $request, int $tableId, int $rowId): JsonResponse
    {
        $cells = $request->request->get('cells', []);
        $link = $request->request->get('link');

        /** @var ?Table */
        $table = app('orm')->getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found.'), Response::HTTP_NOT_FOUND);
        }

        $table->updateRow($rowId, $cells, $link);

        return new JsonResponse(['listid' => $tableId, 'rowid' => $rowId]);
    }

    /**
     * Remove a row from a table.
     *
     * @param Request $request
     * @param int     $tableId
     * @param int     $rowId
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function removeRow(Request $request, int $tableId, int $rowId): JsonResponse
    {
        /** @var ?Table */
        $table = app('orm')->getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found.'), Response::HTTP_NOT_FOUND);
        }

        $table->removeRow($rowId);

        return new JsonResponse(['listid' => $tableId, 'rowid' => $rowId]);
    }
}
