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
     * Page showing the site structure.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $openCategories = explode('<', $request->cookies->get('openkat', ''));
        $openCategories = array_map('intval', $openCategories);

        $siteTreeService = new SiteTreeService();

        $data = $this->basicPageData($request);
        $data['siteTree'] = $siteTreeService->getSiteTreeData($openCategories);

        $content = Render::render('admin/getSiteTree', $data);

        return new Response($content);
    }

    /**
     * Get the label for a folded tree widget.
     *
     * @param Request $request
     * @param int     $id
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

    /**
     * Page picker widget.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function pageWidget(Request $request): Response
    {
        $openCategories = explode('<', $request->cookies->get('openkat', ''));
        $openCategories = array_map('intval', $openCategories);

        $siteTreeService = new SiteTreeService();
        $data = [
            'siteTree' => $siteTreeService->getSiteTreeData(
                $openCategories,
                'pages',
                $request->cookies->get('activekat', -1)
            ),
        ];

        $content = Render::render('admin/pagelist', $data);

        return new Response($content);
    }

    /**
     * List all site products.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function inventory(Request $request): Response
    {
        Render::addLoadedTable('bind');
        Render::addLoadedTable('kat');
        Render::addLoadedTable('krav');
        Render::addLoadedTable('maerke');
        Render::addLoadedTable('sider');
        Render::sendCacheHeader($request);

        $categoryId = $request->request->get('kat', '');
        $sort = $request->get('sort', 'navn');

        $sortOptions = [
            'id'     => 'ID',
            'navn'   => 'Navn',
            'varenr' => 'Varenummer',
            'for'    => 'Før pris',
            'pris'   => 'Nu Pris',
            'dato'   => 'Sidst ændret',
            'maerke' => 'Mærke',
            'krav'   => 'Krav',
        ];

        $reverseOrder = false;
        if ('-' === mb_substr($sort, 0, 1)) {
            $sort = mb_substr($sort, 1);
            $reverseOrder = true;
        }

        $sort = isset($sortOptions[$sort]) ? $sort : 'navn';

        $categories = ORM::getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL');
        if ('' !== $categoryId) {
            $categories = [ORM::getOne(Category::class, $categoryId)];
        }

        $data = [
            'sortOptions'  => $sortOptions,
            'sort'         => $sort,
            'reverseOrder' => $reverseOrder,
            'categories'   => $categories,
            'pathPrefix'   => '',
            'categoryId'   => $categoryId,
        ];
        $content = Render::render('admin/listview', $data);

        return new Response($content);
    }
}
