<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\File;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Service\SiteTreeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageController extends AbstractAdminController
{
    /**
     * Create or edit pages.
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @throws InvalidInput
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
            /** @var ?Page */
            $page = ORM::getOne(Page::class, $id);
            if (!$page) {
                throw new InvalidInput(_('The page does not exist'));
            }

            foreach ($page->getCategories() as $category) {
                $bindings[$category->getId()] = $category->getPath();
            }

            foreach ($page->getAccessories() as $accessory) {
                $path = '';
                if ($root = $accessory->getPrimaryCategory()) {
                    $path = $root->getPath();
                }

                $accessories[$accessory->getId()] = $path . '/' . $accessory->getTitle();
            }
        }

        $siteTreeService = new SiteTreeService();

        $data = [
            'textWidth'    => Config::get('text_width'),
            'thumbWidth'   => Config::get('thumb_width'),
            'siteTree'     => $siteTreeService->getSiteTreeData($openCategories, 'categories', $selectedId),
            'requirements' => ORM::getByQuery(Requirement::class, 'SELECT * FROM `krav` ORDER BY navn'),
            'brands'       => ORM::getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn'),
            'page'         => $page,
            'bindings'     => $bindings,
            'accessories'  => $accessories,
        ] + $this->basicPageData($request);

        $content = Render::render('admin/redigerside', $data);

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
        if ($request->request->has('iconId')) {
            /** @var ?File */
            $icon = ORM::getOne(File::class, $request->request->getInt('iconId'));
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
     * Search page.
     *
     * @param Request $request
     *
     * @throws InvalidInput
     *
     * @return Response
     */
    public function search(Request $request): Response
    {
        $text = $request->get('text', '');
        if ('' === $text) {
            throw new InvalidInput(_('You must enter a search word.'));
        }

        $pages = $this->findPages($text);

        $template = $request->isXmlHttpRequest() ? 'admin/partial-search' : 'admin/search';
        $html = Render::render($template, ['text' => $text, 'pages' => $pages]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['id' => 'canvas', 'html' => $html]);
        }

        return new Response($html);
    }

    /**
     * Add a page to page relation.
     *
     * @param Request $request
     * @param int     $pageId
     * @param int     $accessoryId
     *
     * @return JsonResponse
     */
    public function addAccessory(Request $request, int $pageId, int $accessoryId): JsonResponse
    {
        $page = ORM::getOne(Page::class, $pageId);
        assert($page instanceof Page);
        /** @var ?Page */
        $accessory = ORM::getOne(Page::class, $accessoryId);
        assert($accessory instanceof Page);
        $page->addAccessory($accessory);

        $path = '';
        if ($root = $accessory->getPrimaryCategory()) {
            $path = $root->getPath();
        }

        return new JsonResponse([
            'pageId'      => $page->getId(),
            'accessoryId' => $accessory->getId(),
            'title'       => $path . '/' . $accessory->getTitle(),
        ]);
    }

    /**
     * Remove a page to page relation.
     *
     * @param Request $request
     * @param int     $pageId
     * @param int     $accessoryId
     *
     * @return JsonResponse
     */
    public function removeAccessory(Request $request, int $pageId, int $accessoryId): JsonResponse
    {
        $page = ORM::getOne(Page::class, $pageId);
        assert($page instanceof Page);
        $accessory = ORM::getOne(Page::class, $accessoryId);
        assert($accessory instanceof Page);
        $page->removeAccessory($accessory);

        return new JsonResponse(['id' => 'accessory' . $accessory->getId()]);
    }

    /**
     * Find pages.
     *
     * @return Page[]
     */
    private function findPages(string $text): array
    {
        //fulltext search dosn't catch things like 3 letter words and some other combos
        $simpleq = preg_replace(
            ['/\s+/u', "/'/u", '/´/u', '/`/u'],
            ['%', '_', '_', '_'],
            $text
        );

        return ORM::getByQuery(
            Page::class,
            "
            SELECT * FROM sider
            WHERE MATCH (navn, text, beskrivelse) AGAINST('" . $text . "') > 0
                OR `navn` LIKE '%" . $simpleq . "%'
                OR `text` LIKE '%" . $simpleq . "%'
                OR `beskrivelse` LIKE '%" . $simpleq . "%'
            ORDER BY MATCH (navn, text, beskrivelse) AGAINST('" . $text . "') DESC
            "
        );
    }
}
