<?php namespace AGCMS\Controller;

use AGCMS\Entity\Category;
use AGCMS\Entity\Table;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Ajax extends Base
{
    /**
     * Return html for a sorted list.
     *
     * @param Request $request
     * @param int     $categoryId Id of current category
     * @param int     $tableId    Id of list
     * @param int     $orderBy    What cell to sort by
     *
     * @return JsonResponse
     */
    public function table(Request $request, int $categoryId, int $tableId, int $orderBy): JsonResponse
    {
        Render::addLoadedTable('lists');
        Render::addLoadedTable('list_rows');
        Render::addLoadedTable('sider');
        Render::addLoadedTable('bind');
        Render::addLoadedTable('kat');
        Render::sendCacheHeader($request);

        $html = '';

        $table = ORM::getOne(Table::class, $tableId);
        assert($table instanceof Table);
        if ($rows = $table->getRows($orderBy)) {
            $data = [
                'orderBy'  => $orderBy,
                'table'    => $table,
                'category' => ORM::getOne(Category::class, $categoryId),
            ];
            $html = Render::render('partial-table', $data);
        }

        return new JsonResponse(['id' => 'table' . $tableId, 'html' => $html]);
    }

    /**
     * Get the html for content bellonging to a category.
     *
     * @param Request $request
     * @param int     $categoryId Id of activ category
     * @param string  $orderBy    What column to sort by
     *
     * @return JsonResponse
     */
    public function category(Request $request, int $categoryId, string $orderBy): JsonResponse
    {
        Render::addLoadedTable('sider');
        Render::addLoadedTable('bind');
        Render::addLoadedTable('kat');
        Render::sendCacheHeader($request);

        $data = [
            'renderable' => ORM::getOne(Category::class, $categoryId),
            'orderBy'    => $orderBy,
        ];

        return new JsonResponse([
            'id'   => 'kat' . $categoryId,
            'html' => Render::render('partial-product-list', $data),
        ]);
    }

    /**
     * Get address from phone number.
     *
     * @param Request $request
     * @param string  $phoneNumber Phone number
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function address(Request $request, string $phoneNumber): JsonResponse
    {
        Render::addLoadedTable('fakturas');
        Render::addLoadedTable('email');
        Render::addLoadedTable('post');
        Render::sendCacheHeader($request);

        $default = [
            'name'     => '',
            'attn'     => '',
            'address1' => '',
            'address2' => '',
            'zipcode'  => '',
            'postbox'  => '',
            'email'    => '',
        ];

        //Try katalog orders
        $address = db()->fetchOne(
            "
            SELECT * FROM (
                SELECT
                    navn name,
                    att attn,
                    adresse address1,
                    '' address2,
                    postnr zipcode,
                    postbox postbox,
                    email
                FROM `fakturas`
                WHERE `tlf1` LIKE " . db()->eandq($phoneNumber) . '
                   OR `tlf2` LIKE ' . db()->eandq($phoneNumber) . '
                ORDER BY id DESC
                LIMIT 1
            ) x
            UNION
            SELECT * FROM (
                SELECT
                    postname name,
                    postatt attn,
                    postaddress address1,
                    postaddress2 address2,
                    postpostalcode zipcode,
                    postpostbox postbox,
                    email
                FROM `fakturas`
                WHERE `posttlf` LIKE ' . db()->eandq($phoneNumber) . "
                ORDER BY id DESC
                LIMIT 1
            ) x
            UNION
            SELECT * FROM (
                SELECT
                    navn name,
                    '' attn,
                    adresse address1,
                    '' address2,
                    post zipcode,
                    '' postbox,
                    email
                FROM `email`
                WHERE `tlf1` LIKE " . db()->eandq($phoneNumber) . '
                   OR `tlf2` LIKE ' . db()->eandq($phoneNumber) . "
                ORDER BY id DESC
                LIMIT 1
            ) x
            UNION
            SELECT * FROM (
                SELECT
                    recName1 name,
                    '' attn,
                    recAddress1 address1,
                    '' address2,
                    recZipCode zipcode,
                    '' postbox,
                    '' email
                FROM `post`
                WHERE `recipientID` LIKE " . db()->eandq($phoneNumber) . '
                ORDER BY id DESC
                LIMIT 1
            ) x
            '
        ) + $default;

        if ($address === $default) {
            throw new InvalidInput(_('The address could not be found.'));
        }

        return new JsonResponse($address);
    }
}
