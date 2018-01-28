<?php namespace AGCMS\Controller;

use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\Interfaces\Renderable;
use AGCMS\ORM;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Site extends Base
{
    /**
     * View a category.
     *
     * @param Request $request
     * @param int     $categoryId
     *
     * @return Response
     */
    public function category(Request $request, int $categoryId): Response
    {
        /** @var ?Category */
        $category = app('orm')->getOne(Category::class, $categoryId);
        if ($redirect = $this->checkCategoryUrl($request, $category)) {
            return $redirect;
        }

        $template = Category::GALLERY === $category->getRenderMode() ? 'tiles' : 'list';

        $renderable = $category;
        $pages = $category->getPages();
        if (1 === count($pages)) {
            /** @var Page */
            $renderable = array_shift($pages);
            $template = 'product';
        }

        $data = [
            'crumbs'          => $category->getBranch(),
            'category'        => $category,
            'renderable'      => $renderable,
        ] + $this->basicPageData();

        $response = $this->render($template, $data);

        return $this->cachedResponse($response);
    }

    /**
     * View the frontpage.
     *
     * @return Response
     */
    public function frontPage(): Response
    {
        $data = [
            'renderable' => app('orm')->getOne(CustomPage::class, 1),
        ] + $this->basicPageData();

        $response = $this->render('index', $data);

        return $this->cachedResponse($response);
    }

    /**
     * View page in the root category.
     *
     * @param Request $request
     * @param int     $pageId
     *
     * @return Response
     */
    public function rootPage(Request $request, int $pageId): Response
    {
        return $this->page($request, 0, $pageId);
    }

    /**
     * View a page.
     *
     * @param Request $request
     * @param int     $categoryId
     * @param int     $pageId
     *
     * @return Response
     */
    public function page(Request $request, int $categoryId, int $pageId): Response
    {
        /** @var ?Category */
        $category = app('orm')->getOne(Category::class, $categoryId);
        /** @var ?Page */
        $page = app('orm')->getOne(Page::class, $pageId);

        if ($redirect = $this->checkPageUrl($request, $category, $page)) {
            return $redirect;
        }

        /** @var Renderable[] */
        $crumbs = $category->getBranch();
        $crumbs[] = $page;

        $data = [
            'crumbs'          => $crumbs,
            'category'        => $category,
            'renderable'      => $page,
        ] + $this->basicPageData();

        $response = $this->render('product', $data);

        return $this->cachedResponse($response);
    }

    /**
     * View a requirement notice.
     *
     * @param Request $request
     * @param int     $requirementId
     *
     * @return Response
     */
    public function requirement(Request $request, int $requirementId): Response
    {
        /** @var ?Requirement */
        $requirement = app('orm')->getOne(Requirement::class, $requirementId);
        if ($redirect = $this->checkRenderableUrl($request, $requirement)) {
            return $redirect;
        }

        $data = [
            'renderable' => $requirement,
        ] + $this->basicPageData();
        $data['crumbs'][] = $requirement;

        $response = $this->render('requirement', $data);

        return $this->cachedResponse($response);
    }

    /**
     * View a brand.
     *
     * @param Request $request
     * @param int     $brandId
     *
     * @return Response
     */
    public function brand(Request $request, int $brandId): Response
    {
        /** @var ?Brand */
        $brand = app('orm')->getOne(Brand::class, $brandId);
        if ($redirect = $this->checkRenderableUrl($request, $brand)) {
            return $redirect;
        }

        if (!$brand->getPages()) {
            return $this->redirectToSearch($request);
        }

        $data = [
            'brand'      => $brand,
            'renderable' => $brand,
        ] + $this->basicPageData();
        $data['crumbs'][] = $brand;

        $response = $this->render('tiles', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Check that the url for a category is correct.
     *
     * Returns a redirect responce if the url is not valid
     *
     * @param Request   $request
     * @param ?Category $category
     *
     * @return ?RedirectResponse
     */
    private function checkCategoryUrl(Request $request, ?Category $category): ?RedirectResponse
    {
        if ($category && !$category->isVisible()) {
            return $this->redirectToSearch($request);
        }

        if ($redirect = $this->checkRenderableUrl($request, $category)) {
            return $redirect;
        }

        return null;
    }

    /**
     * Check that the url for a page is correct.
     *
     * Returns a redirect responce if the url is not valid
     *
     * @param Request   $request
     * @param ?Category $category
     * @param ?Page     $page
     *
     * @return ?RedirectResponse
     */
    private function checkPageUrl(Request $request, ?Category $category, ?Page $page): ?RedirectResponse
    {
        if (!$page || $page->isInactive()) {
            if ($category && $category->getParent() && $category->isVisible()) {
                $status = $page ? Response::HTTP_FOUND : Response::HTTP_MOVED_PERMANENTLY;

                return $this->redirect($request, $category->getCanonicalLink(), $status);
            }

            return $this->redirectToSearch($request);
        }

        if ($page->getCanonicalLink($category) !== urldecode($request->getPathInfo())) {
            return $this->redirect($request, $page->getCanonicalLink($category), Response::HTTP_MOVED_PERMANENTLY);
        }

        return null;
    }

    /**
     * Check that the url for a renderable is correct.
     *
     * Returns a redirect responce if the url is not valid
     *
     * @param Request     $request
     * @param ?Renderable $renderable
     *
     * @return ?RedirectResponse
     */
    private function checkRenderableUrl(Request $request, ?Renderable $renderable): ?RedirectResponse
    {
        if (!$renderable) {
            return $this->redirectToSearch($request);
        }

        if ($renderable->getCanonicalLink() !== urldecode($request->getPathInfo())) {
            return $this->redirect($request, $renderable->getCanonicalLink(), Response::HTTP_MOVED_PERMANENTLY);
        }

        return null;
    }
}
