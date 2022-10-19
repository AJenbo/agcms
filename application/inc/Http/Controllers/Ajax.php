<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidInput;
use App\Models\Category;
use App\Models\Table;
use App\Services\DbService;
use App\Services\OrmService;
use App\Services\RenderService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Ajax extends Base
{
    public const MESSAGE_ADDRESS_NOT_FOUND = 'The address could not be found.';

    /**
     * Return html for a sorted list.
     *
     * @param int $orderBy What cell to sort by
     *
     * @exception InvalidInput
     */
    public function table(Request $request, int $categoryId, int $tableId, int $orderBy): JsonResponse
    {
        app(DbService::class)->addLoadedTable('lists', 'list_rows', 'sider', 'bind', 'kat');
        /** @var JsonResponse */
        $response = $this->cachedResponse(new JsonResponse());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $html = '';

        $orm = app(OrmService::class);

        $table = $orm->getOne(Table::class, $tableId);
        if (!$table) {
            throw new InvalidInput(_('Table not found.'), JsonResponse::HTTP_NOT_FOUND);
        }

        if ($table->getRows()) {
            $data = [
                'orderBy'  => $orderBy,
                'table'    => $table,
                'category' => $orm->getOne(Category::class, $categoryId),
            ];
            $html = app(RenderService::class)->render('partial-table', $data);
        }

        return $response->setData(['id' => 'table' . $tableId, 'html' => $html]);
    }

    /**
     * Get the html for content bellonging to a category.
     *
     * @param int    $categoryId Id of activ category
     * @param string $orderBy    What column to sort by
     */
    public function category(Request $request, int $categoryId, string $orderBy): JsonResponse
    {
        app(DbService::class)->addLoadedTable('sider', 'bind', 'kat');
        /** @var JsonResponse */
        $response = $this->cachedResponse(new JsonResponse());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $data = [
            'renderable' => app(OrmService::class)->getOne(Category::class, $categoryId),
            'orderBy'    => $orderBy,
        ];

        return $response->setData([
            'id'   => 'kat' . $categoryId,
            'html' => app(RenderService::class)->render('partial-product-list', $data),
        ]);
    }

    /**
     * Get address from phone number.
     *
     * @throws InvalidInput
     */
    public function address(Request $request, string $phoneNumber): JsonResponse
    {
        $db = app(DbService::class);

        $db->addLoadedTable('fakturas', 'email', 'post');
        /** @var JsonResponse */
        $response = $this->cachedResponse(new JsonResponse());
        if ($response->isNotModified($request)) {
            return $response;
        }

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
        $address = $db->fetchOne(
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
                WHERE `tlf1` LIKE " . $db->quote($phoneNumber) . '
                   OR `tlf2` LIKE ' . $db->quote($phoneNumber) . '
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
                WHERE `posttlf` LIKE ' . $db->quote($phoneNumber) . "
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
                WHERE `tlf1` LIKE " . $db->quote($phoneNumber) . '
                   OR `tlf2` LIKE ' . $db->quote($phoneNumber) . "
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
                WHERE `recipientID` LIKE " . $db->quote($phoneNumber) . '
                ORDER BY id DESC
                LIMIT 1
            ) x
            '
        ) + $default;

        if ($address === $default) {
            throw new InvalidInput(_(self::MESSAGE_ADDRESS_NOT_FOUND), JsonResponse::HTTP_NOT_FOUND);
        }

        return $response->setData($address);
    }
}
