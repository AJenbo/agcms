<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Requirement;
use App\Services\ConfigService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RequirementController extends AbstractAdminController
{
    /**
     * Index page for requirements.
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData($request);
        $data['requirements'] = app(OrmService::class)->getByQuery(Requirement::class, 'SELECT * FROM `krav` ORDER BY navn');

        return $this->render('admin/krav', $data);
    }

    public function create(Request $request): JsonResponse
    {
        $title = $request->getRequestString('title') ?? '';
        $html = $request->getRequestString('html') ?? '';
        $html = purifyHTML($html);

        if ('' === $title || '' === $html) {
            throw new InvalidInput(_('You must enter a name and a text for the requirement.'));
        }

        $requirement = new Requirement(['title' => $title, 'html' => $html]);
        $requirement->save();

        return new JsonResponse(['id' => $requirement->getId()]);
    }

    /**
     * Page for editing or creating a requirement.
     */
    public function editPage(Request $request, ?int $id = null): Response
    {
        $data = $this->basicPageData($request);
        $data['textWidth'] = ConfigService::getInt('text_width');
        $data['requirement'] = $id ? app(OrmService::class)->getOne(Requirement::class, $id) : null;

        return $this->render('admin/editkrav', $data);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $title = $request->getRequestString('title') ?? '';
        $html = valstring($request->get('html', ''));
        $html = purifyHTML($html);

        if ('' === $title || '' === $html) {
            throw new InvalidInput(_('You must enter a name and a text for the requirement.'));
        }

        $requirement = app(OrmService::class)->getOne(Requirement::class, $id);
        if (!$requirement) {
            throw new InvalidInput(_('Requirement not found.'), Response::HTTP_NOT_FOUND);
        }

        $requirement->setHtml($html)->setTitle($title)->save();

        return new JsonResponse(['id' => $id]);
    }

    /**
     * Delete a requirement.
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $requirement = app(OrmService::class)->getOne(Requirement::class, $id);
        if ($requirement) {
            $requirement->delete();
        }

        return new JsonResponse(['id' => 'krav' . $id]);
    }
}
