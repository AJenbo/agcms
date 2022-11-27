<?php

namespace App\Models;

use App\DTO\InvoiceItem;
use App\Enums\InvoiceStatus;
use App\Services\ConfigService;
use App\Services\DbService;
use App\Services\EmailService;

class Invoice extends AbstractEntity
{
    /** Table name in database. */
    public const TABLE_NAME = 'fakturas';

    // Backed by DB

    /** @var int createTime */
    private int $timeStamp = 0;

    /** @var ?int Finalized time */
    private ?int $timeStampPay;

    /** @var float Full amount */
    private float $amount = 0.00;

    /** @var string Billing name */
    private string $name = '';

    /** @var string Billing attention */
    private string $attn = '';

    /** @var string Billing address */
    private string $address = '';

    /** @var string Billing postbox */
    private string $postbox = '';

    /** @var string Billing zipcode */
    private string $postcode = '';

    /** @var string Billing city */
    private string $city = '';

    /** @var string Billing country */
    private string $country = '';

    /** @var string Billing email */
    private string $email = '';

    /** @var string Billing phone number */
    private string $phone1 = '';

    /** @var string Billing mobile number */
    private string $phone2 = '';

    /** @var bool Is the shipping address different from the billing address */
    private bool $hasShippingAddress = false;

    /** @var string Shipping phone number */
    private string $shippingPhone = '';

    /** @var string Shipping name */
    private string $shippingName = '';

    /** @var string Shipping attention */
    private string $shippingAttn = '';

    /** @var string Shipping first address line */
    private string $shippingAddress = '';

    /** @var string Shipping secound address line */
    private string $shippingAddress2 = '';

    /** @var string Shipping postbox */
    private string $shippingPostbox = '';

    /** @var string Shipping zipcode */
    private string $shippingPostcode = '';

    /** @var string Shipping city */
    private string $shippingCity = '';

    /** @var string Shipping country */
    private string $shippingCountry = '';

    /** @var string Client visible note */
    private string $note = '';

    /** @var string Name of responsible cleark */
    private string $clerk = '';

    private InvoiceStatus $status = InvoiceStatus::New;

    /** @var float Shipping price */
    private float $shipping = 0.00;

    /** @var float Tax percentage */
    private float $vat = 0.25;

    /** @var bool Has product prices been entered with vat added */
    private bool $preVat = true;

    /** @var bool Has the money been transfered */
    private bool $transferred = false;

    /** @var string Name of used electronic payment method */
    private string $cardtype = '';

    /** @var string Internal reference */
    private string $iref = '';

    /** @var string External reference */
    private string $eref = '';

    /** @var bool Has the invoice been sent to the customer */
    private bool $sent = false;

    /** @var string Email of responsible department */
    private string $department = '';
    private string $internalNote = '';
    private ?int $paymentId = null;

    // Dynamic

    /** @var array<int, InvoiceItem> */
    private array $items = [];

    public function __construct(array $data = [])
    {
        $this->setItemData(strval($data['item_data'] ?? '[]'))
            ->setHasShippingAddress(boolval($data['has_shipping_address'] ?? false))
            ->setTimeStamp(intval($data['timestamp'] ?? time()))
            ->setTimeStampPay(intval($data['timestamp_pay'] ?? 0))
            ->setAmount(floatval($data['amount'] ?? 0.00))
            ->setName(strval($data['name'] ?? ''))
            ->setAttn(strval($data['attn'] ?? ''))
            ->setAddress(strval($data['address'] ?? ''))
            ->setPostbox(strval($data['postbox'] ?? ''))
            ->setPostcode(strval($data['postcode'] ?? ''))
            ->setCity(strval($data['city'] ?? ''))
            ->setCountry(strval($data['country'] ?? 'DK'))
            ->setEmail(strval($data['email'] ?? ''))
            ->setPhone1(strval($data['phone1'] ?? ''))
            ->setPhone2(strval($data['phone2'] ?? ''))
            ->setShippingPhone(strval($data['shipping_phone'] ?? ''))
            ->setShippingName(strval($data['shipping_name'] ?? ''))
            ->setShippingAttn(strval($data['shipping_attn'] ?? ''))
            ->setShippingAddress(strval($data['shipping_address'] ?? ''))
            ->setShippingAddress2(strval($data['shipping_address2'] ?? ''))
            ->setShippingPostbox(strval($data['shipping_postbox'] ?? ''))
            ->setShippingPostcode(strval($data['shipping_postcode'] ?? ''))
            ->setShippingCity(strval($data['shipping_city'] ?? ''))
            ->setShippingCountry(strval($data['shipping_country'] ?? 'DK'))
            ->setNote(strval($data['note'] ?? ''))
            ->setInternalNote(strval($data['internal_note'] ?? ''))
            ->setClerk(strval($data['clerk'] ?? ''))
            ->setStatus(InvoiceStatus::from(strval($data['status'] ?? 'new')))
            ->setShipping(floatval($data['shipping'] ?? 0.00))
            ->setVat(floatval($data['vat'] ?? 0.25))
            ->setPreVat(boolval($data['pre_vat'] ?? true))
            ->setTransferred(boolval($data['transferred'] ?? false))
            ->setCardtype(strval($data['cardtype'] ?? ''))
            ->setIref(strval($data['iref'] ?? ''))
            ->setEref(strval($data['eref'] ?? ''))
            ->setSent(boolval($data['sent'] ?? false))
            ->setDepartment(strval($data['department'] ?? ''))
            ->setId(intOrNull($data['id'] ?? null));
    }

    /**
     * Clone invoice.
     */
    public function __clone()
    {
        parent::__clone();
        $this->status = InvoiceStatus::New;
        $this->timeStamp = time();
        $this->timeStampPay = null;
        $this->sent = false;
        $this->transferred = false;
    }

    /**
     * Set create time.
     *
     * @return $this
     */
    public function setTimeStamp(int $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    /**
     * Get create time.
     */
    public function getTimeStamp(): int
    {
        return $this->timeStamp;
    }

    /**
     * Get time the payment was finalized.
     *
     * @return $this
     */
    public function setTimeStampPay(int $timeStampPay): self
    {
        $this->timeStampPay = $timeStampPay;

        return $this;
    }

    /**
     * Get time the payment was finalized.
     *
     * @return ?int
     */
    public function getTimeStampPay(): ?int
    {
        return $this->timeStampPay;
    }

    /**
     * Set payment id.
     *
     * @return $this
     */
    public function setPaymentId(int $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    /**
     * Get payment id.
     *
     * @return ?int
     */
    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    /**
     * Set total amount.
     *
     * @return $this
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get total amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Set billing name.
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    /**
     * Get billing name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set billing attention.
     *
     * @return $this
     */
    public function setAttn(string $attn): self
    {
        $this->attn = trim($attn);

        return $this;
    }

    /**
     * Get billing attention.
     */
    public function getAttn(): string
    {
        return $this->attn;
    }

    /**
     * Set billing address.
     *
     * @return $this
     */
    public function setAddress(string $address): self
    {
        $this->address = trim($address);

        return $this;
    }

    /**
     * Get billing address.
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Set billing postbox.
     *
     * @return $this
     */
    public function setPostbox(string $postbox): self
    {
        $this->postbox = trim($postbox);

        return $this;
    }

    /**
     * Get billing postbox.
     */
    public function getPostbox(): string
    {
        return $this->postbox;
    }

    /**
     * Set billing zipcode.
     *
     * @return $this
     */
    public function setPostcode(string $postcode): self
    {
        $this->postcode = trim($postcode);

        return $this;
    }

    /**
     * Get billing zipcode.
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * Set billing city.
     *
     * @return $this
     */
    public function setCity(string $city): self
    {
        $this->city = trim($city);

        return $this;
    }

    /**
     * Get billing city.
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Set billing country.
     *
     * @return $this
     */
    public function setCountry(string $country): self
    {
        $this->country = trim($country);

        return $this;
    }

    /**
     * Get billing country.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Set billing email.
     *
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = trim($email);

        return $this;
    }

    /**
     * Get billing email.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set phone number.
     *
     * @return $this
     */
    public function setPhone1(string $phone1): self
    {
        $this->phone1 = trim($phone1);

        return $this;
    }

    /**
     * Get phone number.
     */
    public function getPhone1(): string
    {
        return $this->phone1;
    }

    /**
     * Set cellphone number.
     *
     * @return $this
     */
    public function setPhone2(string $phone2): self
    {
        $this->phone2 = trim($phone2);

        return $this;
    }

    /**
     * Get cellphone number.
     */
    public function getPhone2(): string
    {
        return $this->phone2;
    }

    /**
     * Set shipping address status.
     *
     * @return $this
     */
    public function setHasShippingAddress(bool $hasShippingAddress): self
    {
        $this->hasShippingAddress = $hasShippingAddress;

        return $this;
    }

    /**
     * Has an address different from the billing address been entered for shipping.
     */
    public function hasShippingAddress(): bool
    {
        return $this->hasShippingAddress;
    }

    /**
     * Set shipping phone number.
     *
     * @return $this
     */
    public function setShippingPhone(string $shippingPhone): self
    {
        $this->shippingPhone = trim($shippingPhone);

        return $this;
    }

    /**
     * Get shipping phone number.
     */
    public function getShippingPhone(): string
    {
        return $this->shippingPhone;
    }

    /**
     * Set shipping name.
     *
     * @return $this
     */
    public function setShippingName(string $shippingName): self
    {
        $this->shippingName = trim($shippingName);

        return $this;
    }

    /**
     * Get shipping name.
     */
    public function getShippingName(): string
    {
        return $this->shippingName;
    }

    /**
     * Set shipping attention line.
     *
     * @return $this
     */
    public function setShippingAttn(string $shippingAttn): self
    {
        $this->shippingAttn = trim($shippingAttn);

        return $this;
    }

    /**
     * Get shipping attention line.
     */
    public function getShippingAttn(): string
    {
        return $this->shippingAttn;
    }

    /**
     * Set first shipping address line.
     *
     * @return $this
     */
    public function setShippingAddress(string $shippingAddress): self
    {
        $this->shippingAddress = trim($shippingAddress);

        return $this;
    }

    /**
     * Get first shipping address line.
     */
    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    /**
     * Set secound shipping address line.
     *
     * @return $this
     */
    public function setShippingAddress2(string $shippingAddress2): self
    {
        $this->shippingAddress2 = trim($shippingAddress2);

        return $this;
    }

    /**
     * Get secound shipping address line.
     */
    public function getShippingAddress2(): string
    {
        return $this->shippingAddress2;
    }

    /**
     * Set shipping postbox.
     *
     * @return $this
     */
    public function setShippingPostbox(string $shippingPostbox): self
    {
        $this->shippingPostbox = trim($shippingPostbox);

        return $this;
    }

    /**
     * Get shipping postbox.
     */
    public function getShippingPostbox(): string
    {
        return $this->shippingPostbox;
    }

    /**
     * Set shipping zipcode.
     *
     * @return $this
     */
    public function setShippingPostcode(string $shippingPostcode): self
    {
        $this->shippingPostcode = trim($shippingPostcode);

        return $this;
    }

    /**
     * Get shipping zipcode.
     */
    public function getShippingPostcode(): string
    {
        return $this->shippingPostcode;
    }

    /**
     * Set shipping city.
     *
     * @return $this
     */
    public function setShippingCity(string $shippingCity): self
    {
        $this->shippingCity = trim($shippingCity);

        return $this;
    }

    /**
     * Get shipping city.
     */
    public function getShippingCity(): string
    {
        return $this->shippingCity;
    }

    /**
     * Set shipping country.
     *
     * @return $this
     */
    public function setShippingCountry(string $shippingCountry): self
    {
        $this->shippingCountry = trim($shippingCountry);

        return $this;
    }

    /**
     * Get shipping country.
     */
    public function getShippingCountry(): string
    {
        return $this->shippingCountry;
    }

    /**
     * Set client note.
     *
     * @return $this
     */
    public function setNote(string $note): self
    {
        $this->note = trim($note);

        return $this;
    }

    /**
     * Get client note.
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * Set clerk name.
     *
     * @return $this
     */
    public function setClerk(string $clerk): self
    {
        $this->clerk = trim($clerk);

        return $this;
    }

    /**
     * Get clerk name.
     */
    public function getClerk(): string
    {
        return $this->clerk;
    }

    /**
     * Set status.
     *
     * @return $this
     */
    public function setStatus(InvoiceStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     */
    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function isHandled(): bool
    {
        return in_array($this->status, [InvoiceStatus::Canceled, InvoiceStatus::Accepted, InvoiceStatus::Giro, InvoiceStatus::Cash], true);
    }

    public function isFinalized(): bool
    {
        return $this->isHandled() || $this->status === InvoiceStatus::PbsOk;
    }

    public function isLocked(): bool
    {
        return $this->status === InvoiceStatus::Locked;
    }

    public function isEditable(): bool
    {
        return !$this->isFinalized() && $this->status !== InvoiceStatus::Rejected;
    }

    public function isNew(): bool
    {
        return $this->status === InvoiceStatus::New;
    }

    /**
     * Set fraight cost.
     *
     * @return $this
     */
    public function setShipping(float $shipping): self
    {
        $this->shipping = $shipping;

        return $this;
    }

    /**
     * Get fraight cost.
     */
    public function getShipping(): float
    {
        return $this->shipping;
    }

    /**
     * Set vat percentage.
     *
     * @return $this
     */
    public function setVat(float $vat): self
    {
        $this->vat = $vat;

        return $this;
    }

    /**
     * Get vat percentage.
     */
    public function getVat(): float
    {
        return $this->vat;
    }

    /**
     * Set the product vat status.
     *
     * @return $this
     */
    public function setPreVat(bool $preVat): self
    {
        $this->preVat = $preVat;

        return $this;
    }

    /**
     * Has product values been entered with vat.
     */
    public function hasPreVat(): bool
    {
        return $this->preVat;
    }

    /**
     * Set the money transfer status.
     *
     * @return $this
     */
    public function setTransferred(bool $transferred): self
    {
        $this->transferred = $transferred;

        return $this;
    }

    /**
     * Has the money transfer been verifyed.
     */
    public function isTransferred(): bool
    {
        return $this->transferred;
    }

    /**
     * Set payment card type.
     *
     * @return $this
     */
    public function setCardtype(string $cardtype): self
    {
        $this->cardtype = trim($cardtype) ?: _('Unknown');

        return $this;
    }

    /**
     * Get payment card type.
     */
    public function getCardtype(): string
    {
        return $this->cardtype;
    }

    /**
     * Set internal reference id.
     *
     * @return $this
     */
    public function setIref(string $iref): self
    {
        $this->iref = trim($iref);

        return $this;
    }

    /**
     * Get internal reference id.
     */
    public function getIref(): string
    {
        return $this->iref;
    }

    /**
     * Set external reference id.
     *
     * @return $this
     */
    public function setEref(string $eref): self
    {
        $this->eref = trim($eref);

        return $this;
    }

    /**
     * Get external reference id.
     */
    public function getEref(): string
    {
        return $this->eref;
    }

    /**
     * Set the payment sent status.
     *
     * @return $this
     */
    public function setSent(bool $sent): self
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Has the payment notice been sent to the customer.
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Set department email.
     *
     * @return $this
     */
    public function setDepartment(string $department): self
    {
        $this->department = trim($department);

        return $this;
    }

    /**
     * Get department email.
     */
    public function getDepartment(): string
    {
        return $this->department;
    }

    /**
     * Set internal note.
     *
     * @return $this
     */
    public function setInternalNote(string $internalNote): self
    {
        $this->internalNote = trim($internalNote);

        return $this;
    }

    /**
     * Get internal note.
     */
    public function getInternalNote(): string
    {
        return $this->internalNote;
    }

    public static function mapFromDB(array $data): array
    {
        $itemQuantities = explode('<', $data['quantities']);
        $itemQuantities = array_map('intval', $itemQuantities);
        $itemValue = explode('<', $data['values']);
        $itemValue = array_map('floatval', $itemValue);
        $itemTitle = explode('<', $data['products']);
        $itemTitle = array_map('html_entity_decode', $itemTitle);

        $items = [];
        foreach ($itemTitle as $key => $title) {
            $items[] = [
                'quantity' => $itemQuantities[$key] ?? 0,
                'title'    => $title,
                'value'    => $itemValue[$key] ?? 0,
            ];
        }
        $items = json_encode($items, JSON_THROW_ON_ERROR);

        $db = app(DbService::class);

        return [
            'id'                   => $data['id'],
            'item_data'            => $items,
            'has_shipping_address' => (bool)$data['altpost'],
            'timestamp'            => strtotime($data['date']) + $db->getTimeOffset(),
            'timestamp_pay'        => strtotime($data['paydate']) + $db->getTimeOffset(),
            'amount'               => (float)$data['amount'],
            'name'                 => $data['navn'],
            'attn'                 => $data['att'],
            'address'              => $data['adresse'],
            'postbox'              => $data['postbox'],
            'postcode'             => $data['postnr'],
            'city'                 => $data['by'],
            'country'              => $data['land'],
            'email'                => $data['email'],
            'phone1'               => $data['tlf1'],
            'phone2'               => $data['tlf2'],
            'shipping_phone'       => $data['posttlf'],
            'shipping_name'        => $data['postname'],
            'shipping_attn'        => $data['postatt'],
            'shipping_address'     => $data['postaddress'],
            'shipping_address2'    => $data['postaddress2'],
            'shipping_postbox'     => $data['postpostbox'],
            'shipping_postcode'    => $data['postpostalcode'],
            'shipping_city'        => $data['postcity'],
            'shipping_country'     => $data['postcountry'],
            'note'                 => $data['note'],
            'internal_note'        => $data['enote'],
            'clerk'                => $data['clerk'],
            'status'               => $data['status'],
            'shipping'             => (float)$data['fragt'],
            'vat'                  => (float)$data['momssats'],
            'pre_vat'              => (bool)$data['premoms'],
            'transferred'          => (bool)$data['transferred'],
            'cardtype'             => $data['cardtype'],
            'iref'                 => $data['iref'],
            'eref'                 => $data['eref'],
            'sent'                 => (bool)$data['sendt'],
            'department'           => $data['department'],
        ];
    }

    /**
     * Set the item data.
     *
     * @param string $itemData Array encoded as JSON
     *
     * @return $this
     */
    public function setItemData(string $itemData): self
    {
        $this->items = [];

        $items = json_decode($itemData, true);
        if (is_array($items)) {
            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? null;
                if (!is_int($quantity)) {
                    $quantity = 0;
                }
                $title = $item['title'] ?? null;
                if (!is_string($title)) {
                    $title = '';
                }
                $value = $item['value'] ?? null;
                if (!is_float($value) && !is_int($value)) {
                    $value = 0.0;
                }
                if (!$quantity && !$title && !$value) {
                    continue;
                }

                $this->items[] = new InvoiceItem($quantity, $title, $value);
            }
        }

        return $this;
    }

    /**
     * @param bool $normalizeVat Some invoices have prices entered including VAT,
     *                           when set to true the function will always return values with out vat
     *
     * @return array<int, InvoiceItem>
     */
    public function getItems(bool $normalizeVat = true): array
    {
        if (!$normalizeVat || !$this->preVat) {
            return $this->items; // Don't normalize value, or already normalized
        }

        $items = [];
        foreach ($this->items as $item) {
            $items[] = new InvoiceItem(
                $item->quantity,
                $item->title,
                $item->value / 1.25
            );
        }

        return $items;
    }

    /**
     * Get full url for administrating this invoice.
     */
    public function getAdminLink(): string
    {
        if (null === $this->id) {
            $this->save();
        }

        return ConfigService::getString('base_url') . '/admin/invoices/' . $this->id . '/';
    }

    /**
     * Get full url for the payment pages.
     */
    public function getLink(): string
    {
        if (null === $this->id) {
            $this->save();
        }

        return ConfigService::getString('base_url') . '/betaling/' . $this->getId() . '/' . $this->getCheckId() . '/';
    }

    /**
     * Check if any of the items have an unspecified value.
     */
    public function hasUnknownPrice(): bool
    {
        foreach ($this->items as $item) {
            if (!$item->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the total product value, excluding vat.
     */
    public function getNetAmount(): float
    {
        $netAmount = 0;
        foreach ($this->getItems() as $item) {
            $netAmount += $item->quantity * $item->value;
        }

        return $netAmount;
    }

    /**
     * Generate the checkId code.
     */
    public function getCheckId(): string
    {
        if (!$this->id) {
            return '';
        }

        return mb_substr(md5($this->id . ConfigService::getString('pbssalt')), 3, 5);
    }

    /**
     * Chekc that a valid customer email has been set.
     */
    public function hasValidEmail(): bool
    {
        return !(!$this->email || !app(EmailService::class)->valideMail($this->email));
    }

    /**
     * Checks that all nessesery contact information has been filled out correctly.
     *
     * @return true[] Key with bool true for each faild feald
     */
    public function getInvalid(): array
    {
        $invalid = [];

        if (!$this->hasValidEmail()) {
            $invalid['email'] = true;
        }
        if (!$this->name) {
            $invalid['name'] = true;
        }
        if (!$this->country) {
            $invalid['country'] = true;
        }
        if (!$this->postbox
            && (!$this->address || ('DK' === $this->country && !preg_match('/\s/ui', $this->address)))
        ) {
            $invalid['address'] = true;
        }
        if (!$this->postcode) {
            $invalid['postcode'] = true;
        }
        if (!$this->city) {
            $invalid['city'] = true;
        }
        if (!$this->country) {
            $invalid['country'] = true;
        }
        if ($this->hasShippingAddress) {
            if (!$this->shippingName) {
                $invalid['shippingName'] = true;
            }
            if (!$this->shippingCountry) {
                $invalid['shippingCountry'] = true;
            }
            if (!$this->shippingPostbox
                && (
                    !$this->shippingAddress
                    || ('DK' === $this->shippingCountry && !preg_match('/\s/ui', $this->shippingAddress))
                )
            ) {
                $invalid['shippingAddress'] = true;
            }
            if (!$this->shippingPostcode) {
                $invalid['shippingPostcode'] = true;
            }
            if (!$this->shippingCity) {
                $invalid['shippingCity'] = true;
            }
            if (!$this->shippingCountry) {
                $invalid['shippingCountry'] = true;
            }
        }

        return $invalid;
    }

    public function getDbArray(): array
    {
        $itemQuantities = [];
        $itemTitle = [];
        $itemValue = [];
        foreach ($this->items as $column) {
            $itemQuantities[] = $column->quantity;
            $itemTitle[] = htmlspecialchars($column->title);
            $itemValue[] = round($column->value, 2);
        }

        $itemQuantities = implode('<', $itemQuantities);
        $itemTitle = implode('<', $itemTitle);
        $itemValue = implode('<', $itemValue);

        $db = app(DbService::class);

        $date = $db->getDateValue($this->timeStamp - $db->getTimeOffset());
        $paydate = $db->quote('0000-00-00');
        if ($this->timeStampPay + $db->getTimeOffset()) {
            $paydate = $db->getDateValue($this->timeStampPay - $db->getTimeOffset());
        }

        return [
            'date'           => $date,
            'paydate'        => $paydate,
            'quantities'     => $db->quote($itemQuantities),
            'products'       => $db->quote($itemTitle),
            'values'         => $db->quote($itemValue),
            'amount'         => $db->escNum($this->amount),
            'navn'           => $db->quote($this->name),
            'att'            => $db->quote($this->attn),
            'adresse'        => $db->quote($this->address),
            'postbox'        => $db->quote($this->postbox),
            'postnr'         => $db->quote($this->postcode),
            'by'             => $db->quote($this->city),
            'land'           => $db->quote($this->country),
            'email'          => $db->quote($this->email),
            'tlf1'           => $db->quote($this->phone1),
            'tlf2'           => $db->quote($this->phone2),
            'altpost'        => (string)(int)$this->hasShippingAddress,
            'posttlf'        => $db->quote($this->shippingPhone),
            'postname'       => $db->quote($this->shippingName),
            'postatt'        => $db->quote($this->shippingAttn),
            'postaddress'    => $db->quote($this->shippingAddress),
            'postaddress2'   => $db->quote($this->shippingAddress2),
            'postpostbox'    => $db->quote($this->shippingPostbox),
            'postpostalcode' => $db->quote($this->shippingPostcode),
            'postcity'       => $db->quote($this->shippingCity),
            'postcountry'    => $db->quote($this->shippingCountry),
            'note'           => $db->quote($this->note),
            'clerk'          => $db->quote($this->clerk),
            'status'         => $db->quote($this->status->value),
            'fragt'          => $db->escNum($this->shipping),
            'momssats'       => $db->escNum($this->vat),
            'premoms'        => (string)(int)$this->preVat,
            'transferred'    => (string)(int)$this->transferred,
            'cardtype'       => $db->quote($this->cardtype),
            'iref'           => $db->quote($this->iref),
            'eref'           => $db->quote($this->eref),
            'sendt'          => (string)(int)$this->sent,
            'payment_id'     => null !== $this->paymentId ? (string)$this->paymentId : 'NULL',
            'department'     => $db->quote($this->department),
            'enote'          => $db->quote($this->internalNote),
        ];
    }
}
