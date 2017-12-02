<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\Exception\Exception;
use AGCMS\ORM;
use AGCMS\Render;
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
        $data['page'] = ORM::getOne(CustomPage::class, $id);
        $data['pageWidth'] = Config::get('text_width');
        if (1 === $id) {
            /** @var ?Category */
            $category = ORM::getOne(Category::class, 0);
            if (!$category) {
                throw new Exception('Root cateogry missing');
            }

            $data['category'] = $category;
            $data['textWidth'] = Config::get('text_width');
            $data['pageWidth'] = Config::get('frontpage_width');
            $data['categories'] = $category->getChildren();
        }

        $content = Render::render('admin/redigerSpecial', $data);

        return new Response($content);
    }
}
