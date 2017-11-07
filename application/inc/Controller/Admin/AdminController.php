<?php namespace AGCMS\Controller\Admin;

use AGCMS\Render;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends AbstractAdminController
{
    /**
     * Admin index page
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData();

        $content = Render::render('admin/index', $data);

        return new Response($content);
    }
}
