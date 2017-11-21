<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Table;
use AGCMS\ORM;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TableController extends AbstractAdminController
{
    /**
     * Add row to a table.
     *
     * @param Request $request
     * @param int     $tableId
     *
     * @return JsonResponse
     */
    public function addRow(Request $request, int $tableId): JsonResponse
    {
        $cells = $request->request->get('cells', []);
        $link = $request->request->get('link');

        /** @var Table */
        $table = ORM::getOne(Table::class, $tableId);
        assert($table instanceof Table);
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
     * @return JsonResponse
     */
    public function updateRow(Request $request, int $tableId, int $rowId): JsonResponse
    {
        $cells = $request->request->get('cells', []);
        $link = $request->request->get('link');

        /** @var Table */
        $table = ORM::getOne(Table::class, $tableId);
        assert($table instanceof Table);
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
     * @return JsonResponse
     */
    public function removeRow(Request $request, int $tableId, int $rowId): JsonResponse
    {
        $table = ORM::getOne(Table::class, $tableId);
        assert($table instanceof Table);
        $table->removeRow($rowId);

        return new JsonResponse(['listid' => $tableId, 'rowid' => $rowId]);
    }
}
