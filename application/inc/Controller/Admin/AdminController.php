<?php namespace AGCMS\Controller\Admin;

use AGCMS\Render;
use AGCMS\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends AbstractAdminController
{
    /**
     * Admin index page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);

        $content = Render::render('admin/index', $data);

        return new Response($content);
    }

    /**
     * Log out current user.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function logout(Request $request): Response
    {
        $request->logout();

        return $this->redirect($request, '/admin/');
    }
}
