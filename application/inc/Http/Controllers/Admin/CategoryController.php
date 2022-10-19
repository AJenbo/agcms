<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Models\Category;
use App\Models\File;
use App\Services\OrmService;
use App\Services\SiteTreeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractAdminController
{
    /**
     * Create or edit category.
     */
    public function index(Request $request, ?int $id = null): Response
    {
        $selectedId = $request->cookies->get('activekat', -1);
        $openCategories = explode('<', $request->cookies->get('openkat', ''));
        $openCategories = array_map('intval', $openCategories);

        $category = null;
        if (null !== $id) {
            $category = app(OrmService::class)->getOne(Category::class, $id);
            if ($category) {
                $parent = $category->getParent();
                $selectedId = $parent ? $parent->getId() : null;
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

        $orm = app(OrmService::class);

        $icon = $iconId ? $orm->getOne(File::class, $iconId) : null;
        $parent = $orm->getOne(Category::class, $parentId);

        $category = new Category([
            'title'             => $title,
            'parent_id'         => $parent ? $parent->getId() : null,
            'icon_id'           => $icon ? $icon->getId() : null,
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
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $orm = app(OrmService::class);

        $category = $orm->getOne(Category::class, $id);
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), Response::HTTP_NOT_FOUND);
        }

        if ($request->request->has('parentId')) {
            $parentId = $request->request->get('parentId');
            $parent = null !== $parentId ? $orm->getOne(Category::class, $parentId) : null;
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
                $icon = $orm->getOne(File::class, $iconId);
            }
            $category->setIcon($icon);
        }

        $category->save();

        if ($request->request->has('subMenusOrder')) {
            $subMenusOrder = $request->request->get('subMenusOrder', '');
            $this->updateKatOrder($subMenusOrder);
        }

        $parent = $category->getParent();

        return new JsonResponse([
            'id'       => 'kat' . $category->getId(),
            'parentId' => $parent ? $parent->getId() : null,
            'title'    => $category->getTitle(),
        ]);
    }

    /**
     * Update the order of categories.
     *
     * @param string $order Comma seporated list of ids
     */
    public function updateKatOrder(string $order): void
    {
        $orm = app(OrmService::class);

        $order = explode(',', $order);
        $order = array_filter($order);
        $order = array_map('intval', $order);
        foreach ($order as $weight => $id) {
            $category = $orm->getOne(Category::class, $id);
            if ($category) {
                $category->setWeight($weight)->save();
            }
        }
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        if ($id < 1) {
            throw new InvalidInput(_('Cannot delete root categories.'), Response::HTTP_LOCKED);
        }

        $category = app(OrmService::class)->getOne(Category::class, $id);
        if ($category) {
            $category->delete();
        }

        return new JsonResponse(['id' => 'kat' . $id]);
    }
}
