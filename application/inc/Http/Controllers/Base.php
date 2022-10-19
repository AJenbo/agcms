<?php

namespace App\Http\Controllers;

use App\Exceptions\Exception;
use App\Http\Request;
use App\Models\Category;
use App\Models\CustomPage;
use App\Services\ConfigService;
use App\Services\DbService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Base extends AbstractController
{
    public const DEFAULT_ICON = '/theme/default/images/intet-foto.jpg';

    /**
     * Generate a redirect to the search page based on the current request url.
     *
     * @throws Exception
     */
    public function redirectToSearch(Request $request): RedirectResponse
    {
        $redirectUrl = '/search/';

        $query = preg_replace(
            [
                '/\/|-|_|\.html|\.htm|\.php|\.gif|\.jpeg|\.jpg|\.png|mærke[0-9]+-|kat[0-9]+-|side[0-9]+-|\.php/u',
                '/[^\w0-9]/u',
                '/([0-9]+)/u',
                '/([[:upper:]]?[[:lower:]]+)/u',
                '/\s+/u',
            ],
            [
                ' ',
                ' ',
                ' \1 ',
                ' \1',
                ' ',
            ],
            rawurldecode($request->getPathInfo())
        );
        if (null === $query) {
            throw new Exception('preg_replace failed');
        }
        $query = trim($query);
        if ($query) {
            $redirectUrl = '/search/results/?q=' . rawurlencode($query) . '&sogikke=&minpris=&maxpris=&maerke=0';
        }

        return redirect($redirectUrl, RedirectResponse::HTTP_SEE_OTHER);
    }

    /**
     * Get the basice render data.
     *
     * @throws Exception
     *
     * @return array<string, mixed>
     */
    protected function basicPageData(): array
    {
        $orm = app(OrmService::class);

        $category = $orm->getOne(Category::class, 0);
        if (!$category) {
            throw new Exception(_('Root category is missing.'));
        }

        return [
            'menu'           => $category->getVisibleChildren(),
            'infoPage'       => $orm->getOne(CustomPage::class, 2),
            'crumbs'         => [$category],
            'category'       => $category,
            'companyName'    => ConfigService::getString('site_name'),
            'companyAddress' => ConfigService::getString('address'),
            'companyZipCode' => ConfigService::getString('postcode'),
            'companyCity'    => ConfigService::getString('city'),
            'companyPhone'   => ConfigService::getString('phone'),
            'companyEmail'   => ConfigService::getDefaultEmail(),
            'localeconv'     => localeconv(),
            'blankImage'     => ConfigService::getString('blank_image', self::DEFAULT_ICON),
            'pageCount'      => ConfigService::getBool('has_count') ? $this->getActivePageCount() : null,
        ];
    }

    /**
     * Get number active page.
     */
    private function getActivePageCount(): int
    {
        $activeCategoryIds = [];
        $categories = app(OrmService::class)->getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            if ($category->isInactive()) {
                continue;
            }
            $activeCategoryIds[] = $category->getId();
        }

        $pages = app(DbService::class)->fetchOne(
            'SELECT COUNT(DISTINCT side) as count FROM bind WHERE kat IN(' . implode(',', $activeCategoryIds) . ')'
        );

        return (int)$pages['count'];
    }
}
