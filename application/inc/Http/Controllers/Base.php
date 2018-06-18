<?php namespace App\Http\Controllers;

use App\Exceptions\Exception;
use App\Models\Category;
use App\Models\CustomPage;
use App\Services\DbService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Base extends AbstractController
{
    const DEFAULT_ICON = '/theme/default/images/intet-foto.jpg';

    /**
     * Generate a redirect to the search page based on the current request url.
     *
     * @param Request $request
     *
     * @return RedirectResponse
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
     * @return array
     */
    protected function basicPageData(): array
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?Category */
        $category = $orm->getOne(Category::class, 0);
        if (!$category) {
            throw new Exception(_('Root category is missing.'));
        }

        return [
            'menu'           => $category->getVisibleChildren(),
            'infoPage'       => $orm->getOne(CustomPage::class, 2),
            'crumbs'         => [$category],
            'category'       => $category,
            'companyName'    => config('site_name'),
            'companyAddress' => config('address'),
            'companyZipCode' => config('postcode'),
            'companyCity'    => config('city'),
            'companyPhone'   => config('phone'),
            'companyEmail'   => first(config('emails'))['address'],
            'localeconv'     => localeconv(),
            'blankImage'     => config('blank_image', self::DEFAULT_ICON),
            'pageCount'      => config('has_count') ? $this->getActivePageCount() : null,
        ];
    }

    /**
     * Get number active page.
     *
     * @return int
     */
    private function getActivePageCount(): int
    {
        /** @var OrmService */
        $orm = app(OrmService::class);

        $activeCategoryIds = [];
        /** @var Category[] */
        $categories = $orm->getByQuery(Category::class, 'SELECT * FROM kat');
        foreach ($categories as $category) {
            if ($category->isInactive()) {
                continue;
            }
            $activeCategoryIds[] = $category->getId();
        }

        /** @var DbService */
        $db = app(DbService::class);

        $pages = $db->fetchOne(
            'SELECT COUNT(DISTINCT side) as count FROM bind WHERE kat IN(' . implode(',', $activeCategoryIds) . ')'
        );

        return $pages['count'];
    }
}
