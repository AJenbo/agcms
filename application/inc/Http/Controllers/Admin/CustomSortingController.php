<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\CustomSorting;
use App\Services\ConfigService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomSortingController extends AbstractAdminController
{
    /**
     * Show list of custom sortings.
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);
        $data['lists'] = app(OrmService::class)->getByQuery(CustomSorting::class, 'SELECT * FROM `tablesort`');

        return $this->render('admin/listsort', $data);
    }

    /**
     * Edit page for custom sorting.
     */
    public function listsortEdit(Request $request, ?int $id = null): Response
    {
        $customSorting = null;
        if (null !== $id) {
            $customSorting = app(OrmService::class)->getOne(CustomSorting::class, $id);
            if (!$customSorting) {
                throw new InvalidInput(_('Custom sorting not found.'), Response::HTTP_NOT_FOUND);
            }
        }

        $data = [
            'customSorting' => $customSorting,
            'textWidth'     => ConfigService::getInt('text_width'),
        ] + $this->basicPageData($request);

        return $this->render('admin/listsort-edit', $data);
    }

    /**
     * Create new custom sorting.
     */
    public function create(Request $request): JsonResponse
    {
        $items = $request->get('items', []);
        $title = $request->get('title');
        if (!$title) {
            throw new InvalidInput(_('You must enter a title.'));
        }

        $customSorting = new CustomSorting([
            'title' => $title,
            'items' => $items,
        ]);
        $customSorting->save();

        return new JsonResponse(['id' => $customSorting->getId()]);
    }

    /**
     * Update custom sorting.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $items = $request->get('items');
        if (!is_array($items)) {
            $items = [];
        }
        $title = $request->getRequestString('title');
        if (!$title) {
            throw new InvalidInput(_('You must enter a title.'));
        }

        $customSorting = app(OrmService::class)->getOne(CustomSorting::class, $id);
        if (!$customSorting) {
            throw new InvalidInput(_('Custom sorting not found.'), Response::HTTP_NOT_FOUND);
        }

        $customSorting->setTitle($title)
            ->setItems($items)
            ->save();

        return new JsonResponse([]);
    }
}
