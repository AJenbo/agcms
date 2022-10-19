<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Http\Controllers\Base;
use App\Http\Request;
use App\Models\Brand;
use App\Models\File;
use App\Services\ConfigService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends AbstractAdminController
{
    /**
     * Index page for brands.
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);
        $data['brands'] = app(OrmService::class)->getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn');
        $data['blank_image'] = ConfigService::getString('blank_image', Base::DEFAULT_ICON);

        return $this->render('admin/maerker', $data);
    }

    /**
     * Page for editing or creating a brand.
     */
    public function editPage(Request $request, int $id): Response
    {
        $data = $this->basicPageData($request);
        $data['brand'] = $id ? app(OrmService::class)->getOne(Brand::class, $id) : null;
        $data['blank_image'] = ConfigService::getString('blank_image', Base::DEFAULT_ICON);

        return $this->render('admin/updatemaerke', $data);
    }

    /**
     * Create new brand.
     */
    public function create(Request $request): JsonResponse
    {
        $title = $request->getRequestString('title') ?? '';
        $link = $request->getRequestString('link') ?? '';
        $iconId = $request->getRequestInt('iconId');
        if (!$title) {
            throw new InvalidInput(_('You must enter a name.'));
        }

        $brand = new Brand(['title' => $title, 'link' => $link, 'icon_id' => $iconId]);
        $brand->save();


        return new JsonResponse(['id' => $brand->getId()]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $title = $request->getRequestString('title');
        $link = $request->getRequestString('link') ?? '';
        $iconId = $request->getRequestInt('iconId');
        if (!$title) {
            throw new InvalidInput(_('You must enter a title.'));
        }

        $orm = app(OrmService::class);

        $brand = $orm->getOne(Brand::class, $id);
        if (!$brand) {
            throw new InvalidInput(_('Brand not found.'), Response::HTTP_NOT_FOUND);
        }

        $icon = null;
        if (null !== $iconId) {
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
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $brand = app(OrmService::class)->getOne(Brand::class, $id);
        if ($brand) {
            $brand->delete();
        }

        return new JsonResponse(['id' => 'maerke' . $id]);
    }
}
