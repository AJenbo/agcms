<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\Page;
use AGCMS\Config;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageController extends AbstractAdminController
{
    /**
     * Page for editing or creating pages
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request, int $id = null): Response
    {
        $selectedId = $request->cookies->get('activekat', -1);
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

        $data = [
            'textWidth' => Config::get('text_width'),
            'thumbWidth' => Config::get('thumb_width'),
            'siteTree' => $this->getSiteTreeData('categories', $selectedId),
            'requirementOptions' => $this->getRequirementOptions(),
            'brands' => ORM::getByQuery(Brand::class, 'SELECT * FROM `maerke` ORDER BY navn'),
            'page' => $page,
            'bindings' => $bindings,
            'accessories' => $accessories,
        ] + $this->basicPageData();

        $content = Render::render('admin/redigerside', $data);

        return new Response($content);
    }

    public function pageList(Request $request): Response
    {
        $data = ['siteTree' => $this->getSiteTreeData('pages', request()->cookies->get('activekat', -1))];

        $content = Render::render('admin/pagelist', $data);

        return new Response($content);
    }

    /**
     * Get site tree data
     *
     * @param string $inputType
     * @param int|null $selectedId
     *
     * @return array
     */
    private function getSiteTreeData(string $inputType = '', int $selectedId = null): array
    {
        $category = null;
        if (null !== $selectedId) {
            $category = ORM::getOne(Category::class, $selectedId);
        }

        return [
            'selectedCategory' => $category,
            'openCategories'   => $this->getOpenCategories($selectedId),
            'includePages'     => (!$inputType || 'pages' === $inputType),
            'inputType'        => $inputType,
            'node'             => ['children' => ORM::getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL')],
            'customPages'      => !$inputType ? ORM::getByQuery(CustomPage::class, 'SELECT * FROM `special` WHERE `id` > 1 ORDER BY `navn`') : [],
        ];
    }

    /**
     * Get ids of open categories
     *
     * @param int|null @selectedId
     *
     * @return int[]
     */
    private function getOpenCategories(int $selectedId = null): array
    {
        $openCategories = explode('<', request()->cookies->get('openkat', ''));
        $openCategories = array_map('intval', $openCategories);

        if (null !== $selectedId) {
            $category = ORM::getOne(Category::class, $selectedId);
            if ($category) {
                assert($category instanceof Category);
                foreach ($category->getBranch() as $category) {
                    $openCategories[] = $category->getId();
                }
            }
        }

        return $openCategories;
    }

    /**
     * List of values for a select for requirements
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
