<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Category;
use AGCMS\Entity\File;
use AGCMS\Exceptions\InvalidInput;
use AGCMS\ORM;
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
            /** @var ?Category */
            $category = app('orm')->getOne(Category::class, $id);
            if ($category) {
                $selectedId = $category->getParent() ? $category->getParent()->getId() : null;
            }
        }

        $siteTreeService = new SiteTreeService();

        $data = [
            'textWidth'    => config('text_width'),
            'emails'       => array_keys(config('emails')),
            'siteTree'     => $siteTreeService->getSiteTreeData($openCategories, 'categories', $selectedId),
            'includePages' => false,
            'category'     => $category,
        ] + $this->basicPageData($request);

        return $this->render('admin/redigerkat', $data);
    }

    /**
     * Create a category.
     *
     * @param Request $request
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $iconId = $request->request->get('icon_id');
        $renderMode = $request->request->getInt('render_mode', Category::GALLERY);
        $email = $request->request->get('email');
        $parentId = $request->request->get('parentId');
        $title = $request->request->get('title');
        if (!$title || null === $parentId) {
            throw new InvalidInput(_('You must enter a title and choose a location for the new category.'));
        }

        $category = new Category([
            'title'             => $title,
            'parent_id'         => $parentId,
            'icon_id'           => $iconId,
            'render_mode'       => $renderMode,
            'email'             => $email,
            'weighted_children' => 0,
            'weight'            => 0,
        ]);
        $category->save();

        return new JsonResponse(['id' => $category->getId()]);
    }

    /**
     * Move category.
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
        /** @var ?Category */
        $category = app('orm')->getOne(Category::class, $id);
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), Response::HTTP_NOT_FOUND);
        }

        if ($request->request->has('parentId')) {
            $parentId = $request->request->get('parentId');
            /** @var ?Category */
            $parent = null !== $parentId ? app('orm')->getOne(Category::class, $parentId) : null;
            if ($parent) {
                foreach ($parent->getBranch() as $node) {
                    if ($node->getId() === $category->getId()) {
                        throw new InvalidInput(_('The category can not be placed under itself.'));
                    }
                }
            }
            $category->setParent($parent);
        }
        if ($request->request->has('title')) {
            $title = $request->request->get('title', '');
            $category->setTitle($title);
        }
        if ($request->request->has('render_mode')) {
            $renderMode = $request->request->getInt('render_mode', Category::GALLERY);
            $category->setRenderMode($renderMode);
        }
        if ($request->request->has('weightedChildren')) {
            $weightedChildren = $request->request->getBoolean('weightedChildren');
            $category->setWeightedChildren($weightedChildren);
        }
        if ($request->request->has('email')) {
            $email = $request->request->get('email');
            $category->setEmail($email);
        }

        if ($request->request->has('icon_id')) {
            $icon = null;
            $iconId = $request->request->get('icon_id');
            if (null !== $iconId) {
                /** @var ?File */
                $icon = app('orm')->getOne(File::class, $iconId);
            }
            $category->setIcon($icon);
        }

        $category->save();

        if ($request->request->has('subMenusOrder')) {
            $subMenusOrder = $request->request->get('subMenusOrder', '');
            $this->updateKatOrder($subMenusOrder);
        }

        return new JsonResponse([
            'id'       => 'kat' . $category->getId(),
            'parentId' => $category->getParent() ? $category->getParent()->getId() : null,
            'title'    => $category->getTitle(),
        ]);
    }

    /**
     * Update the order of categories.
     *
     * @param string $order Comma seporated list of ids
     *
     * @return void
     */
    public function updateKatOrder(string $order): void
    {
        $order = explode(',', $order);
        $order = array_filter($order);
        $order = array_map('intval', $order);
        foreach ($order as $weight => $id) {
            /** @var ?Category */
            $category = app('orm')->getOne(Category::class, $id);
            if ($category) {
                $category->setWeight($weight)->save();
            }
        }
    }

    /**
     * Delete category.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        if ($id < 1) {
            throw new InvalidInput(_('Cannot delete root categories.'), Response::HTTP_LOCKED);
        }

        /** @var ?Category */
        $category = app('orm')->getOne(Category::class, $id);
        if ($category) {
            $category->delete();
        }

        return new JsonResponse(['id' => 'kat' . $id]);
    }
}
