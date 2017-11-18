<?php namespace AGCMS\Service;

use AGCMS\Entity\Category;
use AGCMS\Entity\CustomPage;
use AGCMS\ORM;

class SiteTreeService
{
    /**
     * Get site tree data.
     *
     * @param int[]    $openCategories
     * @param string   $inputType
     * @param int|null $selectedId
     *
     * @return array
     */
    public function getSiteTreeData(array $openCategories, string $inputType = '', int $selectedId = null): array
    {
        $category = null;
        if (null !== $selectedId) {
            $category = ORM::getOne(Category::class, $selectedId);
        }

        $rootCategories = ORM::getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL');

        $customPages = [];
        if (!$inputType) {
            $customPages = ORM::getByQuery(CustomPage::class, 'SELECT * FROM `special` WHERE `id` > 1 ORDER BY `navn`');
        }

        return [
            'selectedCategory' => $category,
            'openCategories'   => $this->getOpenCategories($openCategories, $selectedId),
            'includePages'     => (!$inputType || 'pages' === $inputType),
            'inputType'        => $inputType,
            'node'             => ['children' => $rootCategories],
            'customPages'      => $customPages,
        ];
    }

    /**
     * Get ids of open categories.
     *
     * @param int[]    $openCategories
     * @param int|null $selectedId
     *
     * @return int[]
     */
    private function getOpenCategories(array $openCategories, int $selectedId = null): array
    {
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
}
