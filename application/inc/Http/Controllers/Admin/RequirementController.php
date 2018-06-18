<?php namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Models\Requirement;
use App\Services\OrmService;
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
        /** @var OrmService */
        $orm = app(OrmService::class);

        $data = $this->basicPageData($request);
        /* @var Requirement[] */
        $data['requirements'] = $orm->getByQuery(Requirement::class, 'SELECT * FROM `krav` ORDER BY navn');

        return $this->render('admin/krav', $data);
    }

    /**
     * Create a requirement.
     *
     * @param Request $request
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $title = $request->get('title', '');
        $html = $request->get('html', '');
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
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return Response
     */
    public function editPage(Request $request, int $id = null): Response
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        $data = $this->basicPageData($request);
        $data['textWidth'] = config('text_width');
        $data['requirement'] = $id ? $orm->getOne(Requirement::class, $id) : null;

        return $this->render('admin/editkrav', $data);
    }

    /**
     * Update requirement.
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
        $title = $request->get('title', '');
        $html = $request->get('html', '');
        $html = purifyHTML($html);

        if ('' === $title || '' === $html) {
            throw new InvalidInput(_('You must enter a name and a text for the requirement.'));
        }

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Requirement */
        $requirement = $orm->getOne(Requirement::class, $id);
        if (!$requirement) {
            throw new InvalidInput(_('Requirement not found.'), Response::HTTP_NOT_FOUND);
        }

        $requirement->setHtml($html)->setTitle($title)->save();

        return new JsonResponse(['id' => $id]);
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
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Requirement */
        $requirement = $orm->getOne(Requirement::class, $id);
        if ($requirement) {
            $requirement->delete();
        }

        return new JsonResponse(['id' => 'krav' . $id]);
    }
}
