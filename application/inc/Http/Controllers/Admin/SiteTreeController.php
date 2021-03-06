<?php namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Models\Category;
use App\Services\DbService;
use App\Services\OrmService;
use App\Services\RenderService;
use App\Services\SiteTreeService;
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

        return $this->render('admin/getSiteTree', $data);
    }

    /**
     * Fetch the content for a category.
     *
     * @param Request $request
     * @param int     $categoryId
     *
     * @return JsonResponse
     */
    public function categoryContent(Request $request, int $categoryId): JsonResponse
    {
        $inputType = $request->get('type', '');
        $openCategories = explode('<', $request->cookies->get('openkat', ''));
        $openCategories = array_map('intval', $openCategories);

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var RenderService */
        $render = app(RenderService::class);

        $data = [
            'openCategories' => $openCategories,
            'includePages'   => (!$inputType || 'pages' === $inputType),
            'inputType'      => $inputType,
            'node'           => $orm->getOne(Category::class, $categoryId),
        ];
        $html = $render->render('admin/partial-kat_expand', $data);

        return new JsonResponse(['id' => $categoryId, 'html' => $html]);
    }

    /**
     * Get the label for a folded tree widget.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function lable(Request $request, int $id): JsonResponse
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Category */
        $category = $orm->getOne(Category::class, $id);
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id'   => 'katsheader',
            'text' => _('Select location:') . ' ' . $category->getPath(),
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

        return $this->render('admin/pagelist', $data);
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
        /** @var DbService */
        $db = app(DbService::class);

        $db->addLoadedTable('bind', 'kat', 'krav', 'maerke', 'sider');
        $response = $this->cachedResponse();
        if ($response->isNotModified($request)) {
            return $response;
        }

        $categoryId = $request->request->get('kat', '');
        $sort = $request->get('sort', 'navn');

        $sortOptions = [
            'id'     => _('ID'),
            'navn'   => _('Title'),
            'varenr' => _('SKU'),
            'for'    => _('Previous price'),
            'pris'   => _('Price'),
            'dato'   => _('Updated'),
            'maerke' => _('Brand'),
            'krav'   => _('Requirement'),
        ];

        $reverseOrder = false;
        if ('-' === mb_substr($sort, 0, 1)) {
            $sort = mb_substr($sort, 1);
            $reverseOrder = true;
        }

        $sort = isset($sortOptions[$sort]) ? $sort : 'navn';

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var Category[] */
        $categories = $orm->getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL');
        if ('' !== $categoryId) {
            /** @var Category[] */
            $categories = [$orm->getOne(Category::class, $categoryId)];
        }

        $data = [
            'sortOptions'  => $sortOptions,
            'sort'         => $sort,
            'reverseOrder' => $reverseOrder,
            'categories'   => $categories,
            'pathPrefix'   => '',
            'categoryId'   => $categoryId,
        ];

        return $this->render('admin/listview', $data, $response);
    }
}
