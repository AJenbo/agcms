<?php namespace AGCMS\Service;

use AGCMS\Entity\Contact;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\Page;
use AGCMS\Entity\Table;
use AGCMS\ORM;
use AGCMS\Render;

class InvoiceService
{
    /**
     * Create an invoice from the client cart array
     *
     * @param array $cart
     *
     * @return Invoice
     */
    public function createFromCart(array $cart): Invoice
    {
        $amount = 0;
        $items = [];
        foreach ($cart['items'] ?? [] as $item) {
            $title = '';
            $quantity = $item['quantity'] ?? null;
            $value = null;
            $pageId = null;
            if ('line' === $item['type']) { // Find item based on price table row
                Render::addLoadedTable('list_rows');
                $listRow = db()->fetchOne('SELECT * FROM `list_rows` WHERE id = ' . $item['id']);
                $table = $listRow ? ORM::getOne(Table::class, $listRow['list_id']) : null;
                if ($table) {
                    assert($table instanceof Table);

                    $pageId = $table->getPage()->getId();
                    if ($table->hasLinks()) {
                        $pageId = intval($listRow['link']);
                    }

                    $cells = explode('<', $listRow['cells']);
                    $cells = array_map('html_entity_decode', $cells);

                    foreach ($table->getColumns() as $i => $column) {
                        if (empty($cells[$i]) || !trim($cells[$i])) {
                            continue;
                        }

                        if (in_array($column['type'], [Table::COLUMN_TYPE_STRING, Table::COLUMN_TYPE_INT], true)) {
                            $title .= ' ' . trim($cells[$i]);
                        } elseif (in_array($column['type'], [Table::COLUMN_TYPE_PRICE, Table::COLUMN_TYPE_PRICE_NEW], true)) {
                            $value = intval($cells[$i]) ?: $value;
                        }
                    }
                    $title = trim($title);
                }
            } elseif ('page' === $item['type']) {
                $pageId = $item['id'] ?? null;
            }

            $page = $pageId ? ORM::getOne(Page::class, $pageId) : null;
            if (!$page || $page->isInactive()) {
                $title = _('Expired');
            } else {
                assert($page instanceof Page);

                if (!$title) {
                    $title = $page->getTitle();
                    if ($page->getSku()) {
                        if ($title) {
                            $title .= ' - ';
                        }

                        $title .= $page->getSku();
                    }
                }
                if (!$value || 'page' === $item['type']) {
                    $value = $value ?: $page->getPrice();
                }
            }

            $items[] = [
                'title' => trim($title),
                'quantity' => $quantity,
                'value' => $value,
            ];

            $amount += $value * $quantity;
        }

        $items = json_encode($items);

        $invoice = new Invoice([
            'item_data' => $items,
            'amount' => $amount,
            'name' => $cart['name'] ?? '',
            'attn' => $cart['attn'] ?? '',
            'address' => $cart['address'] ?? '',
            'postbox' => $cart['postbox'] ?? '',
            'postcode' => $cart['postcode'] ?? '',
            'city' => $cart['city'] ?? '',
            'country' => $cart['country'] ?? 'DK',
            'email' => $cart['email'] ?? '',
            'phone1' => $cart['phone1'] ?? '',
            'phone2' => $cart['phone2'] ?? '',
            'has_shipping_address' => (bool) ($cart['hasShippingAddress'] ?? false),
            'shipping_phone' => $cart['shippingPhone'] ?? '',
            'shipping_name' => $cart['shippingName'] ?? '',
            'shipping_attn' => $cart['shippingAttn'] ?? '',
            'shipping_address' => $cart['shippingAddress'] ?? '',
            'shipping_address2' => $cart['shippingAddress2'] ?? '',
            'shipping_postbox' => $cart['shippingPostbox'] ?? '',
            'shipping_postcode' => $cart['shippingPostcode'] ?? '',
            'shipping_city' => $cart['shippingCity'] ?? '',
            'shipping_country' => $cart['shippingCountry'] ?? 'DK',
            'note' => $cart['note'] ?? '',
        ]);

        return $invoice;
    }

    /**
     * Generate additional order comments based on cart options.
     *
     * @param array $cart
     *
     * @return string
     */
    public function generateExtraNote(array $cart): string
    {
        $notes = [];
        switch ($cart['paymethod'] ?? '') {
            case 'creditcard':
                $notes[] = _('I would like to pay via credit card.');
                break;
            case 'bank':
                $notes[] = _('I would like to pay via bank transaction.');
                break;
            case 'cash':
                $notes[] = _('I would like to pay via cash.');
                break;
        }
        switch ($cart['delevery'] ?? '') {
            case 'pickup':
                $notes[] = _('I will pick up the goods in your shop.');
                break;
            case 'postal':
                $notes[] = _('Please send the goods by mail.');
                break;
        }

        return implode("\n", $notes);
    }

    /**
     * Add the customer to the malinglist
     *
     * @param Invoice $invoice
     * @param string  $clientIp
     *
     * @return void
     */
    public function addToAddressBook(Invoice $invoice, string $clientIp): void
    {
        $countries = [];
        include _ROOT_ . '/inc/countries.php';
        /** @var Contact */
        $conteact = ORM::getOneByQuery(
            Contact::class,
            'SELECT * FROM email WHERE email = ' . db()->eandq($invoice->getEmail())
        );
        if (!$conteact) {
            $conteact = new Contact([
                'name'       => $invoice->getName(),
                'email'      => $invoice->getEmail(),
                'address'    => $invoice->getAddress(),
                'country'    => $countries[$invoice->getCountry()] ?? '',
                'postcode'   => $invoice->getPostcode(),
                'city'       => $invoice->getCity(),
                'phone1'     => $invoice->getPhone1(),
                'phone2'     => $invoice->getPhone2(),
                'newsletter' => true,
                'ip'         => $clientIp,
            ]);
            $conteact->save();
            return;
        }
        assert($conteact instanceof Contact);

        $conteact->setName($invoice->getName())
            ->setEmail($invoice->getEmail())
            ->setAddress($invoice->getAddress())
            ->setCountry($countries[$invoice->getCountry()] ?? '')
            ->setPostcode($invoice->getPostcode())
            ->setCity($invoice->getCity())
            ->setPhone1($invoice->getPhone1())
            ->setPhone2($invoice->getPhone2())
            ->setPhone2($invoice->getPhone2())
            ->setNewsletter(true)
            ->setIp($clientIp)
            ->save();
    }
}
