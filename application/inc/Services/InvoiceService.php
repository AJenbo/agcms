<?php namespace App\Services;

use App\Application;
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
     *
     * @return Invoice
     */
    public function createFromCart(array $cart): Invoice
    {
        /** @var DbService */
        $db = app(DbService::class);
        /** @var OrmService */
        $orm = app(OrmService::class);
        $amount = 0;
        $items = [];
        foreach ($cart['items'] ?? [] as $item) {
            $title = '';
            $quantity = $item['quantity'] ?? null;
            $value = null;
            $pageId = null;
            if ('line' === $item['type']) { // Find item based on price table row
                $db->addLoadedTable('list_rows');
                $listRow = $db->fetchOne('SELECT * FROM `list_rows` WHERE id = ' . $item['id']);
                /** @var ?Table */
                $table = $listRow ? $orm->getOne(Table::class, (int)$listRow['list_id']) : null;
                if ($table) {
                    $pageId = $table->getPage()->getId();
                    if ($table->hasLinks() && $listRow['link']) {
                        $pageId = (int) $listRow['link'];
                    }

                    $cells = explode('<', $listRow['cells']);
                    $cells = array_map('html_entity_decode', $cells);

                    foreach ($table->getColumns() as $i => $column) {
                        if (empty($cells[$i]) || !trim($cells[$i])) {
                            continue;
                        }

                        if (in_array($column['type'], [Table::COLUMN_TYPE_STRING, Table::COLUMN_TYPE_INT], true)) {
                            $title .= ' ' . trim($cells[$i]);
                        } elseif (in_array(
                            $column['type'],
                            [Table::COLUMN_TYPE_PRICE, Table::COLUMN_TYPE_PRICE_NEW],
                            true
                        )) {
                            $value = (int) $cells[$i] ?: $value;
                        }
                    }
                    $title = trim($title);
                }
            } elseif ('page' === $item['type']) {
                $pageId = $item['id'] ?? null;
            }

            /** @var ?Page */
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

        $items = json_encode($items);

        $addressData = $this->cleanAddressData($cart);

        $invoice = new Invoice($addressData + [
            'item_data' => $items,
            'amount'    => $amount,
            'note'      => $cart['note'] ?? '',
        ]);

        return $invoice;
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
            'has_shipping_address' => (bool) ($data['hasShippingAddress'] ?? false),
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
     *
     * @return string
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
     *
     * @param Invoice $invoice
     * @param ?string $clientIp
     *
     * @return void
     */
    public function addToAddressBook(Invoice $invoice, ?string $clientIp): void
    {
        /** @var DbService */
        $db = app(DbService::class);
        /** @var OrmService */
        $orm = app(OrmService::class);
        /** @var Application */
        $app = app();
        /** @var string[] */
        $countries = include $app->basePath('/inc/countries.php');
        /** @var ?Contact */
        $conteact = $orm->getOneByQuery(
            Contact::class,
            'SELECT * FROM email WHERE email = ' . $db->quote($invoice->getEmail())
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
     * @param Invoice              $invoice
     * @param User                 $user
     * @param string               $action
     * @param array<string, mixed> $updates
     *
     * @return void
     */
    public function invoiceBasicUpdate(Invoice $invoice, User $user, string $action, array $updates): void
    {
        $status = $invoice->getStatus();

        if ('new' === $invoice->getStatus()) {
            if ('lock' === $action) {
                $status = 'locked';
            }
            $invoice->setTimeStamp(strtotime($updates['date']));
            $invoice->setShipping($updates['shipping']);
            $invoice->setAmount($updates['amount']);
            $invoice->setVat($updates['vat']);
            $invoice->setPreVat($updates['preVat']);
            $invoice->setIref($updates['iref']);
            $invoice->setEref($updates['eref']);
            $invoice->setName($updates['name']);
            $invoice->setAttn($updates['attn']);
            $invoice->setAddress($updates['address']);
            $invoice->setPostbox($updates['postbox']);
            $invoice->setPostcode($updates['postcode']);
            $invoice->setCity($updates['city']);
            $invoice->setCountry($updates['country']);
            $invoice->setEmail($updates['email']);
            $invoice->setPhone1($updates['phone1']);
            $invoice->setPhone2($updates['phone2']);
            $invoice->setHasShippingAddress($updates['hasShippingAddress']);
            if ($updates['hasShippingAddress']) {
                $invoice->setShippingPhone($updates['shippingPhone']);
                $invoice->setShippingName($updates['shippingName']);
                $invoice->setShippingAttn($updates['shippingAttn']);
                $invoice->setShippingAddress($updates['shippingAddress']);
                $invoice->setShippingAddress2($updates['shippingAddress2']);
                $invoice->setShippingPostbox($updates['shippingPostbox']);
                $invoice->setShippingPostcode($updates['shippingPostcode']);
                $invoice->setShippingCity($updates['shippingCity']);
                $invoice->setShippingCountry($updates['shippingCountry']);
            }
            $invoice->setItemData(json_encode($updates['lines']) ?: '[]');
        }

        if (isset($updates['note'])) {
            if ('new' !== $invoice->getStatus()) {
                $updates['note'] = trim($invoice->getNote() . "\n" . $updates['note']);
            }
            $invoice->setNote($updates['note']);
        }

        $invoice->setInternalNote($updates['internalNote']);

        if (!$invoice->getDepartment() && 1 === count(config('emails'))) {
            $email = first(config('emails'))['address'];
            $invoice->setDepartment($email);
        } elseif (!empty($updates['department'])) {
            $invoice->setDepartment($updates['department']);
        }

        if (!$invoice->getClerk()) {
            $invoice->setClerk($user->getFullName());
        }

        if (('giro' === $action || 'cash' === $action)
            && in_array($invoice->getStatus(), ['new', 'locked', 'rejected'], true)
        ) {
            $status = $action;
        }

        if (!$invoice->isFinalized()) {
            if (in_array($action, ['cancel', 'giro', 'cash'], true)
                || ('lock' === $action && 'locked' !== $invoice->getStatus())
            ) {
                $invoice->setTimeStampPay(strtotime($updates['paydate'] ?? '') ?: time());
            }

            if ('cancel' === $action) {
                if ('pbsok' === $invoice->getStatus()) {
                    $this->annulPayment($invoice);
                }
                $status = 'canceled';
            }

            if (isset($updates['clerk']) && $user->hasAccess(User::ADMINISTRATOR)) {
                $invoice->setClerk($updates['clerk']);
            }
        }

        $invoice->setStatus($status)->save();
    }

    /**
     * Accept payment.
     *
     * @param Invoice $invoice
     *
     * @throws Exception
     *
     * @return void
     */
    public function capturePayment(Invoice $invoice): void
    {
        $epayment = $this->getPayment($invoice);
        if (!$epayment->confirm()) {
            throw new Exception(_('Failed to capture payment.'));
        }

        $invoice->setStatus('accepted')
            ->setTimeStampPay(time())
            ->save();
    }

    /**
     * Cancle payment.
     *
     * @param Invoice $invoice
     *
     * @throws Exception
     *
     * @return void
     */
    public function annulPayment(Invoice $invoice): void
    {
        $epayment = $this->getPayment($invoice);
        if (!$epayment->annul()) {
            throw new Exception(_('Failed to cancel payment.'));
        }

        if ('pbsok' === $invoice->getStatus()) {
            $invoice->setStatus('rejected')->save();
        }
    }

    /**
     * Get payment.
     *
     * @param Invoice $invoice
     *
     * @return Epayment
     */
    private function getPayment(Invoice $invoice): Epayment
    {
        $epaymentService = new EpaymentService(config('pbsid'), config('pbspwd'));

        return $epaymentService->getPayment(config('pbsfix') . $invoice->getId());
    }

    /**
     * Send payment email to client.
     *
     * @param Invoice $invoice
     *
     * @throws InvalidInput
     *
     * @return void
     */
    public function sendInvoice(Invoice $invoice): void
    {
        if (!$invoice->hasValidEmail()) {
            throw new InvalidInput(_('Email is not valid.'));
        }

        if (!$invoice->getDepartment() && 1 === count(config('emails'))) {
            $email = first(config('emails'))['address'];
            $invoice->setDepartment($email);
        } elseif (!$invoice->getDepartment()) {
            throw new InvalidInput(_('You have not selected a sender.'));
        }
        if ($invoice->getAmount() < 0.01) {
            throw new InvalidInput(_('The invoice must be of at least 1 cent.'));
        }

        $subject = _('Online payment for ') . config('site_name');
        $emailTemplate = 'email/invoice';
        if ($invoice->isSent()) {
            $subject = 'Elektronisk faktura vedr. ordre';
            $emailTemplate = 'email/invoice-reminder';
        }

        /** @var RenderService */
        $render = app(RenderService::class);
        $emailBody = $render->render(
            $emailTemplate,
            [
                'invoice'    => $invoice,
                'localeconv' => localeconv(),
                'siteName'   => config('site_name'),
                'address'    => config('address'),
                'postcode'   => config('postcode'),
                'city'       => config('city'),
                'phone'      => config('phone'),
            ]
        );

        $email = new Email([
            'subject'          => $subject,
            'body'             => $emailBody,
            'senderName'       => config('site_name'),
            'senderAddress'    => $invoice->getDepartment(),
            'recipientName'    => $invoice->getName(),
            'recipientAddress' => $invoice->getEmail(),
        ]);

        /** @var EmailService */
        $emailService = app(EmailService::class);
        $emailService->send($email);

        if ('new' === $invoice->getStatus()) {
            $invoice->setStatus('locked');
        }

        $invoice->setSent(true)
            ->save();
    }
}
