<?php namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Models\CustomSorting;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomSortingController extends AbstractAdminController
{
    /**
     * Show list of custom sortings.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        $data = $this->basicPageData($request);
        $data['lists'] = $orm->getByQuery(CustomSorting::class, 'SELECT * FROM `tablesort`');

        return $this->render('admin/listsort', $data);
    }

    /**
     * Edit page for custom sorting.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function listsortEdit(Request $request, int $id = null): Response
    {
        $customSorting = null;
        if (null !== $id) {
            /** @var OrmService */
            $orm = app(OrmService::class);

            /** @var ?CustomSorting */
            $customSorting = $orm->getOne(CustomSorting::class, $id);
            if (!$customSorting) {
                throw new InvalidInput(_('Custom sorting not found.'), Response::HTTP_NOT_FOUND);
            }
        }

        $data = [
            'customSorting' => $customSorting,
            'textWidth'     => config('text_width'),
        ] + $this->basicPageData($request);

        return $this->render('admin/listsort-edit', $data);
    }

    /**
     * Create new custom sorting.
     *
     * @param Request $request
     *
     * @return JsonResponse
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
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $items = $request->get('items', []);
        $title = $request->get('title');
        if (!$title) {
            throw new InvalidInput(_('You must enter a title.'));
        }

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?CustomSorting */
        $customSorting = $orm->getOne(CustomSorting::class, $id);
        if (!$customSorting) {
            throw new InvalidInput(_('Custom sorting not found.'), Response::HTTP_NOT_FOUND);
        }

        $customSorting->setTitle($title)
            ->setItems($items)
            ->save();

        return new JsonResponse([]);
    }
}
