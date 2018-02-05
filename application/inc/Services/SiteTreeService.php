<?php namespace App\Services;

use App\Models\Category;
use App\Models\CustomPage;

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
            /** @var ?Category */
            $category = app('orm')->getOne(Category::class, $selectedId);
        }

        /** @var Category[] */
        $rootCategories = app('orm')->getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL');

        $customPages = [];
        if (!$inputType) {
            /** @var CustomPage[] */
            $customPages = app('orm')->getByQuery(
                CustomPage::class,
                'SELECT * FROM `special` WHERE `id` > 1 ORDER BY `navn`'
            );
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
            /** @var ?Category */
            $category = app('orm')->getOne(Category::class, $selectedId);
            if ($category) {
                foreach ($category->getBranch() as $category) {
                    $openCategories[] = $category->getId();
                }
            }
        }

        return $openCategories;
    }
}
