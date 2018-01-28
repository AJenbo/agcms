<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\Exception\Exception;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomPageController extends AbstractAdminController
{
    /**
     * Page for editing a custom page.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws Exception
     *
     * @return Response
     */
    public function index(Request $request, int $id): Response
    {
        $data = $this->basicPageData($request);
        $data['page'] = app('orm')->getOne(CustomPage::class, $id);
        $data['pageWidth'] = config('text_width');
        if (1 === $id) {
            /** @var ?Category */
            $category = app('orm')->getOne(Category::class, 0);
            if (!$category) {
                throw new Exception(_('Root category is missing.'));
            }

            $data['category'] = $category;
            $data['textWidth'] = config('text_width');
            $data['pageWidth'] = config('frontpage_width');
            $data['categories'] = $category->getChildren();
        }

        return $this->render('admin/redigerSpecial', $data);
    }

    /**
     * Update custome page.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws Exception
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var ?CustomPage */
        $page = app('orm')->getOne(CustomPage::class, $id);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), Response::HTTP_NOT_FOUND);
        }

        $title = $request->get('title', '');
        $html = $request->get('html');
        $html = purifyHTML($html);

        if ($title) {
            $page->setTitle($title);
        }
        $page->setHtml($html)->save();

        if (1 === $id) {
            /** @var ?Category */
            $category = app('orm')->getOne(Category::class, 0);
            if (!$category) {
                throw new Exception(_('Root category is missing.'));
            }

            $category->setTitle($title)->save();
        }

        return new JsonResponse([]);
    }
}
