<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Exception;
use App\Exceptions\InvalidInput;
use App\Http\Controllers\Base;
use App\Http\Request;
use App\Models\Brand;
use App\Models\Category;
use App\Models\File;
use App\Models\Page;
use App\Models\Requirement;
use App\Services\ConfigService;
use App\Services\DbService;
use App\Services\OrmService;
use App\Services\RenderService;
use App\Services\SiteTreeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PageController extends AbstractAdminController
{
    /**
     * Create or edit pages.
     */
    public function index(Request $request, ?int $id = null): Response
    {
        $selectedId = valint($request->cookies->get('activekat', -1));
        $openCategories = explode('<', valstring($request->cookies->get('openkat', '')));
        $openCategories = array_map('intval', $openCategories);

        $orm = app(OrmService::class);

        $page = null;
        $bindings = [];
        $accessories = [];
        if (null !== $id) {
            $page = $orm->getOne(Page::class, $id);
            if (!$page) {
                throw new InvalidInput(_('Page not found.'), Response::HTTP_NOT_FOUND);
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
            'textWidth'    => ConfigService::getInt('text_width'),
            'thumbWidth'   => ConfigService::getInt('thumb_width'),
            'siteTree'     => $siteTreeService->getSiteTreeData($openCategories, 'categories', $selectedId),
            'requirements' => $orm->getByQuery(Requirement::class, 'SELECT * FROM `krav` ORDER BY navn'),
            'brands'       => $orm->getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn'),
            'page'         => $page,
            'bindings'     => $bindings,
            'accessories'  => $accessories,
            'blank_image'  => ConfigService::getString('blank_image', Base::DEFAULT_ICON),
        ] + $this->basicPageData($request);

        return $this->render('admin/redigerside', $data);
    }

    /**
     * Create new page and attach it to a category.
     */
    public function createPage(Request $request): JsonResponse
    {
        $category = app(OrmService::class)->getOne(Category::class, $request->request->getInt('categoryId'));
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), Response::HTTP_NOT_FOUND);
        }

        $page = new Page([
            'title'          => $request->getRequestString('title') ?? '',
            'keywords'       => $request->getRequestString('keywords') ?? '',
            'excerpt'        => $request->getRequestString('excerpt') ?? '',
            'html'           => purifyHTML($request->getRequestString('html') ?? ''),
            'sku'            => $request->getRequestString('sku') ?? '',
            'icon_id'        => $request->getRequestInt('iconId'),
            'requirement_id' => $request->getRequestInt('requirementId'),
            'brand_id'       => $request->getRequestInt('brandId'),
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
     */
    public function updatePage(Request $request, int $id): JsonResponse
    {
        $orm = app(OrmService::class);

        $icon = null;
        if ($request->request->has('iconId')) {
            $icon = $orm->getOne(File::class, $request->request->getInt('iconId'));
        }

        $page = $orm->getOne(Page::class, $id);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), Response::HTTP_NOT_FOUND);
        }

        $page->setKeywords($request->getRequestString('keywords') ?? '')
            ->setPrice($request->request->getInt('price'))
            ->setSku($request->getRequestString('sku') ?? '')
            ->setOldPrice($request->request->getInt('oldPrice'))
            ->setExcerpt($request->getRequestString('excerpt') ?? '')
            ->setRequirementId($request->getRequestInt('requirementId'))
            ->setBrandId($request->getRequestInt('brandId'))
            ->setIcon($icon)
            ->setPriceType($request->request->getInt('priceType'))
            ->setOldPriceType($request->request->getInt('oldPriceType'))
            ->setHtml(purifyHTML($request->getRequestString('html') ?? ''))
            ->setTitle($request->getRequestString('title') ?? '')
            ->save();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Delete page.
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $page = app(OrmService::class)->getOne(Page::class, $id);
        if ($page) {
            $page->delete();
        }

        return new JsonResponse(['class' => 'side' . $id]);
    }

    /**
     * Attach a page to a category.
     */
    public function addToCategory(Request $request, int $id, int $categoryId): JsonResponse
    {
        $orm = app(OrmService::class);

        $page = $orm->getOne(Page::class, $id);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), Response::HTTP_NOT_FOUND);
        }

        $category = $orm->getOne(Category::class, $categoryId);
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), Response::HTTP_NOT_FOUND);
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
     */
    public function removeFromCategory(Request $request, int $id, int $categoryId): JsonResponse
    {
        $orm = app(OrmService::class);

        $page = $orm->getOne(Page::class, $id);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), Response::HTTP_NOT_FOUND);
        }

        $category = $orm->getOne(Category::class, $categoryId);
        if (!$category) {
            throw new InvalidInput(_('Category not found.'), Response::HTTP_NOT_FOUND);
        }

        $result = ['pageId' => $page->getId(), 'deleted' => [], 'added' => null];
        if ((-1 === $category->getId() && 1 === count($page->getCategories())) || !$page->isInCategory($category)) {
            return new JsonResponse($result);
        }

        if (1 === count($page->getCategories())) {
            $inactiveCategory = $orm->getOne(Category::class, -1);
            if (!$inactiveCategory) {
                throw new InvalidInput(_('Category not found.'), Response::HTTP_NOT_FOUND);
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
     */
    public function search(Request $request): Response
    {
        $text = valstring($request->get('text', ''));
        if (!$text) {
            throw new InvalidInput(_('You must enter a search word.'));
        }

        $pages = $this->findPages($text);
        $data = ['text' => $text, 'pages' => $pages];

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['id' => 'canvas', 'html' => app(RenderService::class)->render('admin/partial-search', $data)]);
        }

        return $this->render('admin/search', $data);
    }

    /**
     * Add a page to page relation.
     */
    public function addAccessory(Request $request, int $pageId, int $accessoryId): JsonResponse
    {
        $orm = app(OrmService::class);

        $page = $orm->getOne(Page::class, $pageId);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), Response::HTTP_NOT_FOUND);
        }

        $accessory = $orm->getOne(Page::class, $accessoryId);
        if (!$accessory) {
            throw new InvalidInput(_('Accessory not found.'), Response::HTTP_NOT_FOUND);
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
     */
    public function removeAccessory(Request $request, int $pageId, int $accessoryId): JsonResponse
    {
        $orm = app(OrmService::class);

        $page = $orm->getOne(Page::class, $pageId);
        if (!$page) {
            throw new InvalidInput(_('Page not found.'), Response::HTTP_NOT_FOUND);
        }

        $accessory = $orm->getOne(Page::class, $accessoryId);
        if (!$accessory) {
            throw new InvalidInput(_('Accessory not found.'), Response::HTTP_NOT_FOUND);
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
        if (null === $simpleq) {
            throw new Exception('preg_replace failed');
        }

        $db = app(DbService::class);

        return app(OrmService::class)->getByQuery(
            Page::class,
            '
            SELECT * FROM sider
            WHERE MATCH (navn, text, beskrivelse) AGAINST(' . $db->quote($text) . ') > 0
                OR `navn` LIKE ' . $db->quote('%' . $simpleq . '%') . '
                OR `text` LIKE ' . $db->quote('%' . $simpleq . '%') . '
                OR `beskrivelse` LIKE ' . $db->quote('%' . $simpleq . '%') . '
            ORDER BY MATCH (navn, text, beskrivelse) AGAINST(' . $db->quote($text) . ') DESC
            '
        );
    }
}
