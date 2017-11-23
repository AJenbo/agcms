<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Brand;
use AGCMS\ORM;
use AGCMS\Render;
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
}
