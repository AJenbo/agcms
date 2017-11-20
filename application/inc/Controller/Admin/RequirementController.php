<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirementController extends AbstractAdminController
{
    /**
     * Index page for requirements.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);
        $data['requirements'] = ORM::getByQuery(Requirement::class, 'SELECT * FROM `krav` ORDER BY navn');
        $content = Render::render('admin/krav', $data);

        return new Response($content);
    }

    /**
     * Page for editing or creating a requirement.
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return Response
     */
    public function editPage(Request $request, int $id = null): Response
    {
        $data = $this->basicPageData($request);
        $data['textWidth'] = Config::get('text_width');
        $data['requirement'] = $id ? ORM::getOne(Requirement::class, $id) : null;

        $content = Render::render('admin/editkrav', $data);

        return new Response($content);
    }
}
