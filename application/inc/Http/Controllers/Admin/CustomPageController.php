<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Exception;
use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Category;
use App\Models\CustomPage;
use App\Services\ConfigService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomPageController extends AbstractAdminController
{
    /**
     * Page for editing a custom page.
     *
     * @throws Exception
     */
    public function index(Request $request, int $id): Response
    {
        $orm = app(OrmService::class);

        $data = $this->basicPageData($request);
        $data['page'] = $orm->getOne(CustomPage::class, $id);
        $data['pageWidth'] = ConfigService::getInt('text_width');
        if (1 === $id) {
            $category = $orm->getOne(Category::class, 0);
            if (!$category) {
                throw new Exception(_('Root category is missing.'));
            }

            $data['category'] = $category;
            $data['textWidth'] = ConfigService::getInt('text_width');
            $data['pageWidth'] = ConfigService::getInt('frontpage_width');
            $data['categories'] = $category->getChildren();
        }

        return $this->render('admin/redigerSpecial', $data);
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $orm = app(OrmService::class);

        $page = $orm->getOne(CustomPage::class, $id);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), Response::HTTP_NOT_FOUND);
        }

        $title = $request->getRequestString('title') ?? '';
        $html = $request->getRequestString('html') ?? '';
        $html = purifyHTML($html);

        if ($title) {
            $page->setTitle($title);
        }
        $page->setHtml($html)->save();

        if (1 === $id) {
            $category = $orm->getOne(Category::class, 0);
            if (!$category) {
                throw new Exception(_('Root category is missing.'));
            }

            $category->setTitle($title)->save();
        }

        return new JsonResponse([]);
    }
}
