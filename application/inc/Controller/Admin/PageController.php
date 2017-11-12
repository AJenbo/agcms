<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\File;
use AGCMS\Entity\Page;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Service\SiteTreeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageController extends AbstractAdminController
{
    /**
     * Page for editing or creating pages.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request, int $id = null): Response
    {
        $selectedId = $request->cookies->get('activekat', -1);
        $openCategories = explode('<', $request->cookies->get('openkat', ''));
        $openCategories = array_map('intval', $openCategories);

        $page = null;
        $bindings = [];
        $accessories = [];
        if (null !== $id) {
            /** @var Page */
            $page = ORM::getOne(Page::class, $id);
            assert($page instanceof Page);
            if ($page) {
                foreach ($page->getCategories() as $category) {
                    $bindings[$category->getId()] = $category->getPath();
                }

                foreach ($page->getAccessories() as $accessory) {
                    $category = $accessory->getPrimaryCategory();
                    $accessories[$accessory->getId()] = $category->getPath() . $accessory->getTitle();
                }
            }
        }

        $siteTreeService = new SiteTreeService();

        $data = [
            'textWidth' => Config::get('text_width'),
            'thumbWidth' => Config::get('thumb_width'),
            'siteTree' => $siteTreeService->getSiteTreeData($openCategories, 'categories', $selectedId),
            'requirementOptions' => $this->getRequirementOptions(),
            'brands' => ORM::getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn'),
            'page' => $page,
            'bindings' => $bindings,
            'accessories' => $accessories,
        ] + $this->basicPageData($request);

        $content = Render::render('admin/redigerside', $data);

        return new Response($content);
    }

    public function pageList(Request $request): Response
    {
        $openCategories = explode('<', $request->cookies->get('openkat', ''));
        $openCategories = array_map('intval', $openCategories);

        $siteTreeService = new SiteTreeService();
        $data = ['siteTree' => $siteTreeService->getSiteTreeData(
            $openCategories,
            'pages',
            $request->cookies->get('activekat', -1)
        )];

        $content = Render::render('admin/pagelist', $data);

        return new Response($content);
    }

    /**
     * Create new page and attach it to a category.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createPage(Request $request): JsonResponse
    {
        $category = ORM::getOne(Category::class, $request->request->get('categoryId'));
        assert($category instanceof Category);

        $page = new Page([
            'title'          => $request->request->get('title'),
            'keywords'       => $request->request->get('keywords'),
            'excerpt'        => $request->request->get('excerpt'),
            'html'           => purifyHTML($request->request->get('html')),
            'sku'            => $request->request->get('sku'),
            'icon_id'        => $request->request->get('iconId'),
            'requirement_id' => $request->request->get('requirementId'),
            'brand_id'       => $request->request->get('brandId'),
            'price'          => $request->request->get('price'),
            'old_price'      => $request->request->get('oldPrice'),
            'price_type'     => $request->request->get('priceType'),
            'old_price_type' => $request->request->get('oldPriceType'),
        ]);
        $page->save();
        $page->addToCategory($category);

        return new JsonResponse(['id' => $page->getId()]);
    }

    /**
     * Update existing page.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updatePage(Request $request, int $id): JsonResponse
    {
        $icon = null;
        if (null !== $request->request->get('iconId')) {
            $icon = ORM::getOne(File::class, $request->request->get('iconId'));
        }

        $page = ORM::getOne(Page::class, $id);
        assert($page instanceof Page);
        $page->setKeywords($request->request->get('keywords'))
            ->setPrice($request->request->get('price'))
            ->setSku($request->request->get('sku'))
            ->setOldPrice($request->request->get('oldPrice'))
            ->setExcerpt($request->request->get('excerpt'))
            ->setRequirementId($request->request->get('requirementId'))
            ->setBrandId($request->request->get('brandId'))
            ->setIcon($icon)
            ->setPriceType($request->request->get('priceType'))
            ->setOldPriceType($request->request->get('oldPriceType'))
            ->setHtml(purifyHTML($request->request->get('html')))
            ->setTitle($request->request->get('title'))
            ->save();

        return new JsonResponse(['success' => true]);
    }

    /**
     * List of values for a select of requirements.
     *
     * @return string[]
     */
    private function getRequirementOptions(): array
    {
        $options = [0 => 'None'];
        $requirements = db()->fetchArray('SELECT id, navn FROM `krav` ORDER BY navn');
        foreach ($requirements as $requirement) {
            $options[$requirement['id']] = $requirement['navn'];
        }

        return $options;
    }
}
