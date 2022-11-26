<?php

namespace App\Services;

use App\Countries;
use App\Enums\ColumnType;
use App\Enums\InvoiceAction;
use App\Enums\InvoiceStatus;
use App\Exceptions\Exception;
use App\Exceptions\InvalidInput;
use App\Models\Contact;
use App\Models\Email;
use App\Models\Epayment;
use App\Models\Invoice;
use App\Models\Page;
use App\Models\Table;
use App\Models\User;

class InvoiceService
{
    /**
     * Create an invoice from the client cart array.
     *
     * @param array<string, mixed> $cart
     */
    public function createFromCart(array $cart): Invoice
    {
        $db = app(DbService::class);
        $orm = app(OrmService::class);
        $amount = 0;
        $items = [];
        if (isset($cart['items']) && is_array($cart['items'])) {
            foreach ($cart['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $title = '';
                $quantity = $item['quantity'] ?? null;
                if (!is_int($quantity) && (!is_string($quantity) || !ctype_digit($quantity))) {
                    $quantity = 0;
                }
                $quantity = (int)$quantity;
                $value = null;
                $pageId = null;
                if ('line' === $item['type']) { // Find item based on price table row
                    $db->addLoadedTable('list_rows');
                    $listRow = $db->fetchOne('SELECT * FROM `list_rows` WHERE id = ' . $item['id']);
                    $table = $listRow ? $orm->getOne(Table::class, (int)$listRow['list_id']) : null;
                    if ($table) {
                        $pageId = $table->getPage()->getId();
                        if ($table->hasLinks() && $listRow['link']) {
                            $pageId = (int)$listRow['link'];
                        }

                        $cells = explode('<', $listRow['cells']);
                        $cells = array_map('html_entity_decode', $cells);

                        foreach ($table->getColumns() as $i => $column) {
                            if (empty($cells[$i]) || !trim($cells[$i])) {
                                continue;
                            }

                            if (in_array($column->type, [ColumnType::String, ColumnType::Int], true)) {
                                $title .= ' ' . trim($cells[$i]);
                            } elseif (in_array(
                                $column->type,
                                [ColumnType::Price, ColumnType::SalesPrice],
                                true
                            )) {
                                $value = (int)$cells[$i];
                            }
                        }
                        $title = trim($title);
                    }
                } elseif ('page' === $item['type']) {
                    $pageId = $item['id'] ?? null;
                }

                $page = $pageId ? $orm->getOne(Page::class, $pageId) : null;
                if (!$page || $page->isInactive()) {
                    $title = _('Expired');
                } else {
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
                    'title'    => trim($title),
                    'quantity' => $quantity,
                    'value'    => $value,
                ];

                $amount += $value * $quantity;
            }
        }

        $items = json_encode($items, JSON_THROW_ON_ERROR);

        $addressData = $this->cleanAddressData($cart);

        return new Invoice($addressData + [
            'item_data' => $items,
            'amount'    => $amount,
            'note'      => $cart['note'] ?? '',
        ]);
    }

    /**
     * Clean up address data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function cleanAddressData(array $data): array
    {
        $data = [
            'name'                 => $data['name'] ?? '',
            'attn'                 => $data['attn'] ?? '',
            'address'              => $data['address'] ?? '',
            'postbox'              => $data['postbox'] ?? '',
            'postcode'             => $data['postcode'] ?? '',
            'city'                 => $data['city'] ?? '',
            'country'              => $data['country'] ?? 'DK',
            'email'                => $data['email'] ?? '',
            'phone1'               => $data['phone1'] ?? '',
            'phone2'               => $data['phone2'] ?? '',
            'has_shipping_address' => (bool)($data['hasShippingAddress'] ?? false),
            'shipping_phone'       => $data['shippingPhone'] ?? '',
            'shipping_name'        => $data['shippingName'] ?? '',
            'shipping_attn'        => $data['shippingAttn'] ?? '',
            'shipping_address'     => $data['shippingAddress'] ?? '',
            'shipping_address2'    => $data['shippingAddress2'] ?? '',
            'shipping_postbox'     => $data['shippingPostbox'] ?? '',
            'shipping_postcode'    => $data['shippingPostcode'] ?? '',
            'shipping_city'        => $data['shippingCity'] ?? '',
            'shipping_country'     => $data['shippingCountry'] ?? 'DK',
        ];
        if ($data['attn'] === $data['name']) {
            $data['attn'] = '';
        }
        if ($data['postbox'] === $data['postcode']) {
            $data['postbox'] = '';
        }
        if ($data['phone1'] === $data['phone2']) {
            $data['phone1'] = '';
        }
        if ($data['shipping_attn'] === $data['shipping_name']) {
            $data['shipping_attn'] = '';
        }
        if ($data['shipping_postbox'] === $data['shipping_postcode']) {
            $data['shipping_postbox'] = '';
        }
        if (!$data['shipping_address2']
            && ($data['shipping_phone'] === $data['phone1'] || $data['shipping_phone'] === $data['phone2'])
            && $data['shipping_name'] === $data['name']
            && $data['shipping_attn'] === $data['attn']
            && $data['shipping_address'] === $data['address']
            && $data['shipping_postbox'] === $data['postbox']
            && $data['shipping_postcode'] === $data['postcode']
            && $data['shipping_city'] === $data['city']
            && $data['shipping_country'] === $data['country']
        ) {
            $data['has_shipping_address'] = false;
        }

        return $data;
    }

    /**
     * Generate additional order comments based on cart options.
     *
     * @param array<string, string> $cart
     */
    public function generateExtraNote(array $cart): string
    {
        $notes = [];
        switch ($cart['payMethod'] ?? '') {
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
        switch ($cart['deleveryMethod'] ?? '') {
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
     * Add the customer to the malinglist.
     */
    public function addToAddressBook(Invoice $invoice, ?string $clientIp): void
    {
        /** @var string[] */
        $countries = Countries::getOrdered();
        $conteact = app(OrmService::class)->getOneByQuery(
            Contact::class,
            'SELECT * FROM email WHERE email = ' . app(DbService::class)->quote($invoice->getEmail())
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
                'subscribed' => true,
                'ip'         => $clientIp ?? '',
            ]);
            $conteact->save();

            return;
        }

        $conteact->setName($invoice->getName())
            ->setEmail($invoice->getEmail())
            ->setAddress($invoice->getAddress())
            ->setCountry($countries[$invoice->getCountry()] ?? '')
            ->setPostcode($invoice->getPostcode())
            ->setCity($invoice->getCity())
            ->setPhone1($invoice->getPhone1())
            ->setPhone2($invoice->getPhone2())
            ->setPhone2($invoice->getPhone2())
            ->setSubscribed(true)
            ->setIp($clientIp ?? '')
            ->save();
    }

    /**
     * Update invoice and mange it's state.
     *
     * @param array<string, mixed> $updates
     */
    public function invoiceBasicUpdate(Invoice $invoice, User $user, InvoiceAction $action, array $updates): void
    {
        $status = $invoice->getStatus();

        if (InvoiceStatus::New === $invoice->getStatus()) {
            if (InvoiceAction::Lock === $action) {
                $status = InvoiceStatus::Locked;
            }
            $invoice->setTimeStamp(strtotime(strval($updates['date'])) ?: time());
            $invoice->setShipping(floatval($updates['shipping']));
            $invoice->setAmount(floatval($updates['amount']));
            $invoice->setVat(floatval($updates['vat']));
            $invoice->setPreVat(boolval($updates['preVat']));
            $invoice->setIref(strval($updates['iref']));
            $invoice->setEref(strval($updates['eref']));
            $invoice->setName(strval($updates['name']));
            $invoice->setAttn(strval($updates['attn']));
            $invoice->setAddress(strval($updates['address']));
            $invoice->setPostbox(strval($updates['postbox']));
            $invoice->setPostcode(strval($updates['postcode']));
            $invoice->setCity(strval($updates['city']));
            $invoice->setCountry(strval($updates['country']));
            $invoice->setEmail(strval($updates['email']));
            $invoice->setPhone1(strval($updates['phone1']));
            $invoice->setPhone2(strval($updates['phone2']));
            $invoice->setHasShippingAddress(boolval($updates['hasShippingAddress']));
            if ($updates['hasShippingAddress']) {
                $invoice->setShippingPhone(strval($updates['shippingPhone']));
                $invoice->setShippingName(strval($updates['shippingName']));
                $invoice->setShippingAttn(strval($updates['shippingAttn']));
                $invoice->setShippingAddress(strval($updates['shippingAddress']));
                $invoice->setShippingAddress2(strval($updates['shippingAddress2']));
                $invoice->setShippingPostbox(strval($updates['shippingPostbox']));
                $invoice->setShippingPostcode(strval($updates['shippingPostcode']));
                $invoice->setShippingCity(strval($updates['shippingCity']));
                $invoice->setShippingCountry(strval($updates['shippingCountry']));
            }
            $invoice->setItemData(json_encode($updates['lines'], JSON_THROW_ON_ERROR) ?: '[]');
        }

        if (isset($updates['note']) && is_string($updates['note'])) {
            $note = $updates['note'];
            if (InvoiceStatus::New !== $invoice->getStatus()) {
                $note = trim($invoice->getNote() . "\n" . $note);
            }
            $invoice->setNote($note);
        }

        $internalNote = $updates['internalNote'] ?? null;
        if (!is_string($internalNote)) {
            $internalNote = '';
        }
        $invoice->setInternalNote($internalNote);

        if (!$invoice->getDepartment() && 1 === count(ConfigService::getEmailConfigs())) {
            $email = ConfigService::getDefaultEmail();
            $invoice->setDepartment($email);
        } elseif (!empty($updates['department']) && is_string($updates['department'])) {
            $invoice->setDepartment($updates['department']);
        }

        if (!$invoice->getClerk()) {
            $invoice->setClerk($user->getFullName());
        }

        if (in_array($invoice->getStatus(), [
            InvoiceStatus::New,
            InvoiceStatus::Locked,
            InvoiceStatus::Rejected,
        ], true)) {
            if (InvoiceAction::Giro === $action) {
                $status = InvoiceStatus::Giro;
            }
            if (InvoiceAction::Cash === $action) {
                $status = InvoiceStatus::Cash;
            }
        }

        if (!$invoice->isFinalized()) {
            if (in_array($action, [InvoiceAction::Cancel, InvoiceAction::Giro, InvoiceAction::Cash], true)
                || (InvoiceAction::Lock === $action && InvoiceStatus::Locked !== $invoice->getStatus())
            ) {
                $date = $updates['paydate'] ?? null;
                if (!is_string($date)) {
                    $date = '';
                }
                $invoice->setTimeStampPay(strtotime($date) ?: time());
            }

            if (InvoiceAction::Cancel === $action) {
                if (InvoiceStatus::PbsOk === $invoice->getStatus()) {
                    $this->annulPayment($invoice);
                }
                $status = InvoiceStatus::Canceled;
            }

            $clerk = $updates['clerk'] ?? null;
            if (is_string($clerk) && $user->hasAccess(User::ADMINISTRATOR)) {
                $invoice->setClerk($clerk);
            }
        }

        $invoice->setStatus($status)->save();
    }

    /**
     * Accept payment.
     *
     * @throws Exception
     */
    public function capturePayment(Invoice $invoice): void
    {
        $epayment = $this->getPayment($invoice);
        if (!$epayment->confirm()) {
            throw new Exception(_('Failed to capture payment.'));
        }

        $invoice->setStatus(InvoiceStatus::Accepted)
            ->setTimeStampPay(time())
            ->save();
    }

    /**
     * Cancle payment.
     *
     * @throws Exception
     */
    public function annulPayment(Invoice $invoice): void
    {
        $epayment = $this->getPayment($invoice);
        if (!$epayment->annul()) {
            throw new Exception(_('Failed to cancel payment.'));
        }

        if (InvoiceStatus::PbsOk === $invoice->getStatus()) {
            $invoice->setStatus(InvoiceStatus::Rejected)->save();
        }
    }

    /**
     * Get payment.
     */
    private function getPayment(Invoice $invoice): Epayment
    {
        $epaymentService = new EpaymentService(ConfigService::getString('pbsid'), ConfigService::getString('pbspwd'));

        return $epaymentService->getPayment(ConfigService::getString('pbsfix') . $invoice->getId());
    }

    /**
     * Send payment email to client.
     *
     * @throws InvalidInput
     */
    public function sendInvoice(Invoice $invoice): void
    {
        if (!$invoice->hasValidEmail()) {
            throw new InvalidInput(_('Email is not valid.'));
        }

        if (!$invoice->getDepartment() && 1 === count(ConfigService::getEmailConfigs())) {
            $email = ConfigService::getDefaultEmail();
            $invoice->setDepartment($email);
        } elseif (!$invoice->getDepartment()) {
            throw new InvalidInput(_('You have not selected a sender.'));
        }
        if ($invoice->getAmount() < 0.01) {
            throw new InvalidInput(_('The invoice must be of at least 1 cent.'));
        }

        $subject = _('Online payment for ') . ConfigService::getString('site_name');
        $emailTemplate = 'email/invoice';
        if ($invoice->isSent()) {
            $subject = 'Elektronisk faktura vedr. ordre';
            $emailTemplate = 'email/invoice-reminder';
        }

        $emailBody = app(RenderService::class)->render(
            $emailTemplate,
            [
                'invoice'    => $invoice,
                'siteName'   => ConfigService::getString('site_name'),
                'address'    => ConfigService::getString('address'),
                'postcode'   => ConfigService::getString('postcode'),
                'city'       => ConfigService::getString('city'),
                'phone'      => ConfigService::getString('phone'),
            ]
        );

        $email = new Email([
            'subject'          => $subject,
            'body'             => $emailBody,
            'senderName'       => ConfigService::getString('site_name'),
            'senderAddress'    => $invoice->getDepartment(),
            'recipientName'    => $invoice->getName(),
            'recipientAddress' => $invoice->getEmail(),
        ]);

        app(EmailService::class)->send($email);

        if (InvoiceStatus::New === $invoice->getStatus()) {
            $invoice->setStatus(InvoiceStatus::Locked);
        }

        $invoice->setSent(true)
            ->save();
    }
}
