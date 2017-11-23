<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Requirement;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * Create a requirement.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $title = $request->get('title', '');
        $html = $request->get('html', '');
        $html = purifyHTML($html);

        if ('' === $title || '' === $html) {
            throw new InvalidInput(_('You must enter a name and a text of the requirement.'));
        }

        $requirement = new Requirement(['title' => $title, 'html' => $html]);
        $requirement->save();

        return new JsonResponse(['id' => $requirement->getId()]);
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

    /**
     * Update requirement.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $title = $request->get('title', '');
        $html = $request->get('html', '');
        $html = purifyHTML($html);

        if ('' === $title || '' === $html) {
            throw new InvalidInput(_('You must enter a name and a text of the requirement.'));
        }

        $requirement = ORM::getOne(Requirement::class, $id);
        $requirement->setHtml($html)->setTitle($title)->save();

        return new JsonResponse(['id' => $requirement->getId()]);
    }

    /**
     * Delete a requirement.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $requirement = ORM::getOne(Requirement::class, $id);
        $requirement->delete();

        return new JsonResponse(['id' => 'krav' . $id]);
    }
}
