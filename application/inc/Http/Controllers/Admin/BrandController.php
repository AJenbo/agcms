<?php namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Http\Controllers\Base;
use App\Models\Brand;
use App\Models\File;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends AbstractAdminController
{
    /**
     * Index page for brands.
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
        $data['brands'] = $orm->getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn');
        $data['blank_image'] = config('blank_image', Base::DEFAULT_ICON);

        return $this->render('admin/maerker', $data);
    }

    /**
     * Page for editing or creating a brand.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function editPage(Request $request, int $id): Response
    {
        /** @var OrmService */
        $orm = app(OrmService::class);
        $data = $this->basicPageData($request);
        $data['brand'] = $id ? $orm->getOne(Brand::class, $id) : null;
        $data['blank_image'] = config('blank_image', Base::DEFAULT_ICON);

        return $this->render('admin/updatemaerke', $data);
    }

    /**
     * Create new brand.
     *
     * @param Request $request
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $title = $request->request->get('title', '');
        $link = $request->request->get('link', '');
        $iconId = $request->request->get('iconId');
        if (!$title) {
            throw new InvalidInput(_('You must enter a name.'));
        }

        $brand = new Brand(['title' => $title, 'link' => $link, 'icon_id' => $iconId]);
        $brand->save();

        return new JsonResponse(['id' => $brand->getId()]);
    }

    /**
     * Update a brand.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $title = $request->request->get('title');
        $link = $request->request->get('link', '');
        $iconId = $request->request->get('iconId');
        if (!$title) {
            throw new InvalidInput(_('You must enter a title.'));
        }

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Brand */
        $brand = $orm->getOne(Brand::class, $id);
        if (!$brand) {
            throw new InvalidInput(_('Brand not found.'), Response::HTTP_NOT_FOUND);
        }

        $icon = null;
        if (null !== $iconId) {
            /** @var ?File */
            $icon = $orm->getOne(File::class, $iconId);
        }

        $brand->setIcon($icon)
            ->setLink($link)
            ->setTitle($title)
            ->save();

        return new JsonResponse(['id' => $brand->getId()]);
    }

    /**
     * Delete a brand.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Brand */
        $brand = $orm->getOne(Brand::class, $id);
        if ($brand) {
            $brand->delete();
        }

        return new JsonResponse(['id' => 'maerke' . $id]);
    }
}
