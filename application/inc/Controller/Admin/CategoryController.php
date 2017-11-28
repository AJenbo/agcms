<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Category;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Service\SiteTreeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractAdminController
{
    /**
     * Create or edit category.
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return Response
     */
    public function index(Request $request, int $id = null): Response
    {
        $selectedId = $request->cookies->get('activekat', -1);
        $openCategories = explode('<', $request->cookies->get('openkat', ''));
        $openCategories = array_map('intval', $openCategories);

        $category = null;
        if (null !== $id) {
            $category = ORM::getOne(Category::class, $id);
            if ($category) {
                assert($category instanceof Category);
                $selectedId = $category->getParent() ? $category->getParent()->getId() : null;
            }
        }

        $siteTreeService = new SiteTreeService();

        $data = [
            'textWidth'    => Config::get('text_width'),
            'emails'       => array_keys(Config::get('emails')),
            'siteTree'     => $siteTreeService->getSiteTreeData($openCategories, 'categories', $selectedId),
            'includePages' => false,
            'category'     => $category,
        ] + $this->basicPageData($request);

        $content = Render::render('admin/redigerkat', $data);

        return new Response($content);
    }

    /**
     * Move category.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function move(Request $request, int $id): JsonResponse
    {
        $parentId = $request->request->getInt('parentId');

        $category = ORM::getOne(Category::class, $id);
        $parent = ORM::getOne(Category::class, $parentId);
        $category->setParent($parent)->save();

        return new JsonResponse(['id' => 'kat' . $id, 'update' => $parentId]);
    }
}
