<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Brand;
use AGCMS\Entity\File;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
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
        $data = $this->basicPageData($request);
        $data['brands'] = ORM::getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn');
        $content = Render::render('admin/maerker', $data);

        return new Response($content);
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
        $data = $this->basicPageData($request);
        $data['brand'] = $id ? ORM::getOne(Brand::class, $id) : null;

        $content = Render::render('admin/updatemaerke', $data);

        return new Response($content);
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

        /** @var ?Brand */
        $brand = ORM::getOne(Brand::class, $id);
        if (!$brand) {
            throw new InvalidInput(_('The brand not found.'));
        }

        $icon = null;
        if (null !== $iconId) {
            /** @var ?File */
            $icon = ORM::getOne(File::class, $iconId);
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
        /** @var ?Brand */
        $brand = ORM::getOne(Brand::class, $id);
        if ($brand) {
            $brand->delete();
        }

        return new JsonResponse(['id' => 'maerke' . $id]);
    }
}
