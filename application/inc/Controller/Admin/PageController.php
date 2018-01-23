<?php namespace AGCMS\Controller\Admin;

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
                throw new InvalidInput(_('Page not found.'), 404);
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
            'textWidth'    => config('text_width'),
            'thumbWidth'   => config('thumb_width'),
            'siteTree'     => $siteTreeService->getSiteTreeData($openCategories, 'categories', $selectedId),
            'requirements' => ORM::getByQuery(Requirement::class, 'SELECT * FROM `krav` ORDER BY navn'),
            'brands'       => ORM::getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn'),
            'page'         => $page,
            'bindings'     => $bindings,
            'accessories'  => $accessories,
            'blank_image'  => config('blank_image', '/theme/default/images/intet-foto.jpg'),
        ] + $this->basicPageData($request);

        return $this->render('admin/redigerside', $data);
    }

    /**
     * Create new page and attach it to a category.
     *
     * @param Request $request
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function createPage(Request $request): JsonResponse
    {
        /** @var ?Category */
        $category = ORM::getOne(Category::class, $request->request->get('categoryId'));
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), 404);
        }

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
     * @param int     $id
     *
     * @throws InvalidInput
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

        /** @var ?Page */
        $page = ORM::getOne(Page::class, $id);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), 404);
        }

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
     * Delete page.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        /** @var ?Page */
        $page = ORM::getOne(Page::class, $id);
        if ($page) {
            $page->delete();
        }

        return new JsonResponse(['class' => 'side' . $id]);
    }

    /**
     * Attach a page to a category.
     *
     * @param Request $request
     * @param int     $id
     * @param int     $categoryId
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function addToCategory(Request $request, int $id, int $categoryId): JsonResponse
    {
        /** @var ?Page */
        $page = ORM::getOne(Page::class, $id);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), 404);
        }

        /** @var ?Category */
        $category = ORM::getOne(Category::class, $categoryId);
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), 404);
        }

        $result = ['pageId' => $page->getId(), 'deleted' => [], 'added' => null];

        if ($page->isInCategory($category)) {
            return new JsonResponse($result);
        }

        $page->addToCategory($category);
        $result['added'] = ['categoryId' => $category->getId(), 'path' => $category->getPath()];

        $rootCategory = $category->getRoot();
        foreach ($page->getCategories() as $node) {
            if ($node->getRoot() === $rootCategory) {
                continue;
            }

            $page->removeFromCategory($node);
            $result['deleted'][] = $node->getId();
        }

        return new JsonResponse($result);
    }

    /**
     * Remove the page from category.
     *
     * @param Request $request
     * @param int     $id
     * @param int     $categoryId
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function removeFromCategory(Request $request, int $id, int $categoryId): JsonResponse
    {
        /** @var ?Page */
        $page = ORM::getOne(Page::class, $id);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), 404);
        }

        /** @var ?Category */
        $category = ORM::getOne(Category::class, $categoryId);
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), 404);
        }

        $result = ['pageId' => $page->getId(), 'deleted' => [], 'added' => null];
        if ((-1 === $category->getId() && 1 === count($page->getCategories())) || !$page->isInCategory($category)) {
            return new JsonResponse($result);
        }

        if (1 === count($page->getCategories())) {
            /** @var ?Category */
            $inactiveCategory = ORM::getOne(Category::class, -1);
            if (!$inactiveCategory) {
                throw new InvalidInput(_('Category not found.'), 404);
            }

            $page->addToCategory($inactiveCategory);
            $result['added'] = ['categoryId' => -1, 'path' => '/' . _('Inactive') . '/'];
        }

        $page->removeFromCategory($category);
        $result['deleted'][] = $category->getId();

        return new JsonResponse($result);
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
        $data = ['text' => $text, 'pages' => $pages];

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['id' => 'canvas', 'html' => Render::render('admin/partial-search', $data)]);
        }

        return $this->render('admin/search', $data);
    }

    /**
     * Add a page to page relation.
     *
     * @param Request $request
     * @param int     $pageId
     * @param int     $accessoryId
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function addAccessory(Request $request, int $pageId, int $accessoryId): JsonResponse
    {
        /** @var ?Page */
        $page = ORM::getOne(Page::class, $pageId);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), 404);
        }

        /** @var ?Page */
        $accessory = ORM::getOne(Page::class, $accessoryId);
        if (!$accessory) {
            throw new InvalidInput(_('Accessory not found.'), 404);
        }

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
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function removeAccessory(Request $request, int $pageId, int $accessoryId): JsonResponse
    {
        /** @var ?Page */
        $page = ORM::getOne(Page::class, $pageId);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), 404);
        }

        /** @var ?Page */
        $accessory = ORM::getOne(Page::class, $accessoryId);
        if (!$accessory) {
            throw new InvalidInput(_('Accessory not found.'), 404);
        }

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

        /** @var Page[] */
        $pages = ORM::getByQuery(
            Page::class,
            '
            SELECT * FROM sider
            WHERE MATCH (navn, text, beskrivelse) AGAINST(' . db()->quote($text) . ') > 0
                OR `navn` LIKE ' . db()->quote('%' . $simpleq . '%') . '
                OR `text` LIKE ' . db()->quote('%' . $simpleq . '%') . '
                OR `beskrivelse` LIKE ' . db()->quote('%' . $simpleq . '%') . '
            ORDER BY MATCH (navn, text, beskrivelse) AGAINST(' . db()->quote($text) . ') DESC
            '
        );

        return $pages;
    }
}
