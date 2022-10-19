<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CustomPage;

class SiteTreeService
{
    /**
     * Get site tree data.
     *
     * @param int[] $openCategories
     *
     * @return array<string, mixed>
     */
    public function getSiteTreeData(array $openCategories, string $inputType = '', ?int $selectedId = null): array
    {
        $orm = app(OrmService::class);

        $category = null;
        if (null !== $selectedId) {
            $category = $orm->getOne(Category::class, $selectedId);
        }

        $rootCategories = $orm->getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL');

        $customPages = [];
        if (!$inputType) {
            $customPages = $orm->getByQuery(
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
     * @param int[] $openCategories
     *
     * @return int[]
     */
    private function getOpenCategories(array $openCategories, ?int $selectedId = null): array
    {
        if (null !== $selectedId) {
            $category = app(OrmService::class)->getOne(Category::class, $selectedId);
            if ($category) {
                foreach ($category->getBranch() as $category) {
                    $openCategories[] = $category->getId();
                }
            }
        }

        return $openCategories;
    }
}
