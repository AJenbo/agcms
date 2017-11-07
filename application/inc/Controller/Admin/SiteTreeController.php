<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Category;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Service\SiteTreeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SiteTreeController extends AbstractAdminController
{
    /**
     * Page for editing or creating pages.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $siteTreeService = new SiteTreeService();

        $data = $this->basicPageData();
        $data['siteTree'] = $siteTreeService->getSiteTreeData();

        $content = Render::render('admin/getSiteTree', $data);

        return new Response($content);
    }

    /**
     * Get the label for a folded tree widget
     *
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function lable(Request $request, int $id): JsonResponse
    {
        $category = ORM::getOne(Category::class, $id);
        assert($category instanceof Category);

        $data = [
            'id'   => 'katsheader',
            'html' => _('Select location:') . ' ' . $category->getPath(),
        ];
        return new JsonResponse($data);
    }
}
