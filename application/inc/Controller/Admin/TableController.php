<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Table;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
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
        $content = Render::render(
            'admin/addlist',
            [
                'tablesorts' => db()->fetchArray('SELECT id, navn title FROM `tablesort`'),
                'page_id'    => $pageId,
            ]
        );

        return new Response($content);
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
        $table = ORM::getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found'));
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
        $table = ORM::getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found'));
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
        $table = ORM::getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found'));
        }

        $table->removeRow($rowId);

        return new JsonResponse(['listid' => $tableId, 'rowid' => $rowId]);
    }
}
