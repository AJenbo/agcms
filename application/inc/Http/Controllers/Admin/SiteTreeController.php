<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Category;
use App\Services\DbService;
use App\Services\OrmService;
use App\Services\RenderService;
use App\Services\SiteTreeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SiteTreeController extends AbstractAdminController
{
    /**
     * Page showing the site structure.
     */
    public function index(Request $request): Response
    {
        $openCategories = explode('<', strval($request->cookies->get('openkat', '')));
        $openCategories = array_map('intval', $openCategories);

        $siteTreeService = new SiteTreeService();

        $data = $this->basicPageData($request);
        $data['siteTree'] = $siteTreeService->getSiteTreeData($openCategories);

        return $this->render('admin/getSiteTree', $data);
    }

    /**
     * Fetch the content for a category.
     */
    public function categoryContent(Request $request, int $categoryId): JsonResponse
    {
        $inputType = $request->getRequestString('type') ?? '';
        $openCategories = explode('<', strval($request->cookies->get('openkat', '')));
        $openCategories = array_map('intval', $openCategories);

        $data = [
            'openCategories' => $openCategories,
            'includePages'   => (!$inputType || 'pages' === $inputType),
            'inputType'      => $inputType,
            'node'           => app(OrmService::class)->getOne(Category::class, $categoryId),
        ];
        $html = app(RenderService::class)->render('admin/partial-kat_expand', $data);

        return new JsonResponse(['id' => $categoryId, 'html' => $html]);
    }

    /**
     * Get the label for a folded tree widget.
     */
    public function lable(Request $request, int $id): JsonResponse
    {
        $category = app(OrmService::class)->getOne(Category::class, $id);
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
     */
    public function pageWidget(Request $request): Response
    {
        $openCategories = explode('<', strval($request->cookies->get('openkat', '')));
        $openCategories = array_map('intval', $openCategories);

        $siteTreeService = new SiteTreeService();
        $data = [
            'siteTree' => $siteTreeService->getSiteTreeData(
                $openCategories,
                'pages',
                intval($request->cookies->get('activekat', -1))
            ),
        ];

        return $this->render('admin/pagelist', $data);
    }

    /**
     * List all site products.
     */
    public function inventory(Request $request): Response
    {
        app(DbService::class)->addLoadedTable('bind', 'kat', 'krav', 'maerke', 'sider');
        $response = $this->cachedResponse();
        if ($response->isNotModified($request)) {
            return $response;
        }

        $categoryId = $request->getRequestInt('kat');
        $sort = $request->getRequestString('sort') ?? 'navn';

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

        $orm = app(OrmService::class);

        $categories = $orm->getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL');
        if ($categoryId) {
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
