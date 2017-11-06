<?php namespace AGCMS\Entity;

use AGCMS\Config;

class Invoice extends AbstractEntity
{
    /**
     * Table name in database.
     */
    const TABLE_NAME = 'fakturas';

    // Backed by DB
    private $timeStamp = 0;
    /** @var ?int */
    private $timeStampPay;
    private $amount = 0.00;
    private $name = '';
    private $attn = '';
    private $address = '';
    private $postbox = '';
    private $postcode = '';
    private $city = '';
    private $country = '';
    private $email = '';
    private $phone1 = '';
    private $phone2 = '';
    private $hasShippingAddress = false;
    private $shippingPhone = '';
    private $shippingName = '';
    private $shippingAttn = '';
    private $shippingAddress = '';
    private $shippingAddress2 = '';
    private $shippingPostbox = '';
    private $shippingPostcode = '';
    private $shippingCity = '';
    private $shippingCountry = '';
    private $note = '';
    private $clerk = '';
    private $status = '';
    private $shipping = 0.00;
    private $vat = 0.25;
    private $preVat = true;
    private $transferred = false;
    private $cardtype = '';
    private $iref = '';
    private $eref = '';
    /** @var bool Has the invoice been sent to the customer */
    private $sent = false;
    private $department = '';
    private $internalNote = '';
    // Dynamic
    /** @var array[] */
    private $items = [];

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setItemData($data['item_data'])
            ->setHasShippingAddress($data['has_shipping_address'] ?? false)
            ->setTimeStamp($data['timestamp'] ?? time())
            ->setTimeStampPay($data['timestamp_pay'] ?? 0)
            ->setAmount($data['amount'] ?? 0.00)
            ->setName($data['name'])
            ->setAttn($data['attn'])
            ->setAddress($data['address'])
            ->setPostbox($data['postbox'])
            ->setPostcode($data['postcode'])
            ->setCity($data['city'])
            ->setCountry($data['country'] ?? 'DK')
            ->setEmail($data['email'])
            ->setPhone1($data['phone1'])
            ->setPhone2($data['phone2'])
            ->setShippingPhone($data['shipping_phone'])
            ->setShippingName($data['shipping_name'])
            ->setShippingAttn($data['shipping_attn'])
            ->setShippingAddress($data['shipping_address'])
            ->setShippingAddress2($data['shipping_address2'])
            ->setShippingPostbox($data['shipping_postbox'])
            ->setShippingPostcode($data['shipping_postcode'])
            ->setShippingCity($data['shipping_city'])
            ->setShippingCountry($data['shipping_country'] ?? 'DK')
            ->setNote($data['note'])
            ->setInternalNote($data['internal_note'] ?? '')
            ->setClerk($data['clerk'] ?? '')
            ->setStatus($data['status'] ?? 'new')
            ->setShipping($data['shipping'] ?? '0.00')
            ->setVat($data['vat'] ?? '0.25')
            ->setPreVat($data['pre_vat'] ?? true)
            ->setTransferred($data['transferred'] ?? false)
            ->setCardtype($data['cardtype'] ?? '')
            ->setIref($data['iref'] ?? '')
            ->setEref($data['eref'] ?? '')
            ->setSent($data['sent'] ?? false)
            ->setDepartment($data['department'] ?? '')
            ->setId($data['id'] ?? null);
    }

    public function setTimeStamp(int $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    public function getTimeStamp(): int
    {
        return $this->timeStamp;
    }

    public function setTimeStampPay(int $timeStampPay): self
    {
        $this->timeStampPay = $timeStampPay;

        return $this;
    }

    public function getTimeStampPay(): int
    {
        return $this->timeStampPay;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAttn(string $attn): self
    {
        $this->attn = trim($attn);

        return $this;
    }

    public function getAttn(): string
    {
        return $this->attn;
    }

    public function setAddress(string $address): self
    {
        $this->address = trim($address);

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setPostbox(string $postbox): self
    {
        $this->postbox = trim($postbox);

        return $this;
    }

    public function getPostbox(): string
    {
        return $this->postbox;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = trim($postcode);

        return $this;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function setCity(string $city): self
    {
        $this->city = trim($city);

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCountry(string $country): self
    {
        $this->country = trim($country);

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setEmail(string $email): self
    {
        $this->email = trim($email);

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setPhone1(string $phone1): self
    {
        $this->phone1 = trim($phone1);

        return $this;
    }

    public function getPhone1(): string
    {
        return $this->phone1;
    }

    public function setPhone2(string $phone2): self
    {
        $this->phone2 = trim($phone2);

        return $this;
    }

    public function getPhone2(): string
    {
        return $this->phone2;
    }

    public function setHasShippingAddress(bool $hasShippingAddress): self
    {
        $this->hasShippingAddress = $hasShippingAddress;

        return $this;
    }

    public function hasShippingAddress(): bool
    {
        return $this->hasShippingAddress;
    }

    public function setShippingPhone(string $shippingPhone): self
    {
        $this->shippingPhone = trim($shippingPhone);

        return $this;
    }

    public function getShippingPhone(): string
    {
        return $this->shippingPhone;
    }

    public function setShippingName(string $shippingName): self
    {
        $this->shippingName = trim($shippingName);

        return $this;
    }

    public function getShippingName(): string
    {
        return $this->shippingName;
    }

    public function setShippingAttn(string $shippingAttn): self
    {
        $this->shippingAttn = trim($shippingAttn);

        return $this;
    }

    public function getShippingAttn(): string
    {
        return $this->shippingAttn;
    }

    public function setShippingAddress(string $shippingAddress): self
    {
        $this->shippingAddress = trim($shippingAddress);

        return $this;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress2(string $shippingAddress2): self
    {
        $this->shippingAddress2 = trim($shippingAddress2);

        return $this;
    }

    public function getShippingAddress2(): string
    {
        return $this->shippingAddress2;
    }

    public function setShippingPostbox(string $shippingPostbox): self
    {
        $this->shippingPostbox = trim($shippingPostbox);

        return $this;
    }

    public function getShippingPostbox(): string
    {
        return $this->shippingPostbox;
    }

    public function setShippingPostcode(string $shippingPostcode): self
    {
        $this->shippingPostcode = trim($shippingPostcode);

        return $this;
    }

    public function getShippingPostcode(): string
    {
        return $this->shippingPostcode;
    }

    public function setShippingCity(string $shippingCity): self
    {
        $this->shippingCity = trim($shippingCity);

        return $this;
    }

    public function getShippingCity(): string
    {
        return $this->shippingCity;
    }

    public function setShippingCountry(string $shippingCountry): self
    {
        $this->shippingCountry = trim($shippingCountry);

        return $this;
    }

    public function getShippingCountry(): string
    {
        return $this->shippingCountry;
    }

    public function setNote(string $note): self
    {
        $this->note = trim($note);

        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setClerk(string $clerk): self
    {
        $this->clerk = trim($clerk);

        return $this;
    }

    public function getClerk(): string
    {
        return $this->clerk;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setShipping(float $shipping): self
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getShipping(): float
    {
        return $this->shipping;
    }

    public function setVat(float $vat): self
    {
        $this->vat = $vat;

        return $this;
    }

    public function getVat(): float
    {
        return $this->vat;
    }

    public function setPreVat(bool $preVat): self
    {
        $this->preVat = $preVat;

        return $this;
    }

    public function hasPreVat(): bool
    {
        return $this->preVat;
    }

    public function setTransferred(bool $transferred): self
    {
        $this->transferred = $transferred;

        return $this;
    }

    public function isTransferred(): bool
    {
        return $this->transferred;
    }

    public function setCardtype(string $cardtype): self
    {
        $this->cardtype = trim($cardtype) ?: _('Unknown');

        return $this;
    }

    public function getCardtype(): string
    {
        return $this->cardtype;
    }

    public function setIref(string $iref): self
    {
        $this->iref = trim($iref);

        return $this;
    }

    public function getIref(): string
    {
        return $this->iref;
    }

    public function setEref(string $eref): self
    {
        $this->eref = trim($eref);

        return $this;
    }

    public function getEref(): string
    {
        return $this->eref;
    }

    public function setSent(bool $sent): self
    {
        $this->sent = $sent;

        return $this;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setDepartment(string $department): self
    {
        $this->department = trim($department);

        return $this;
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function setInternalNote(string $internalNote): self
    {
        $this->internalNote = trim($internalNote);

        return $this;
    }

    public function getInternalNote(): string
    {
        return $this->internalNote;
    }

    /**
     * Map data from DB table to entity.
     *
     * @param array The data from the database
     *
     * @return array
     */
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
        $items = json_encode($items);

        return [
            'id'                   => $data['id'],
            'item_data'            => $items,
            'has_shipping_address' => (bool) $data['altpost'],
            'timestamp'            => strtotime($data['date']) + db()->getTimeOffset(),
            'timestamp_pay'        => strtotime($data['paydate']) + db()->getTimeOffset(),
            'amount'               => (float) $data['amount'],
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
            'shipping'             => (float) $data['fragt'],
            'vat'                  => (float) $data['momssats'],
            'pre_vat'              => (bool) $data['premoms'],
            'transferred'          => (bool) $data['transferred'],
            'cardtype'             => $data['cardtype'],
            'iref'                 => $data['iref'],
            'eref'                 => $data['eref'],
            'sent'                 => (bool) $data['sendt'],
            'department'           => $data['department'],
        ];
    }

    /**
     * Set the item data.
     *
     * @param string $itemData Array encoded as JSON
     *
     * @return self
     */
    public function setItemData(string $itemData): self
    {
        $this->items = [];

        $items = json_decode($itemData, true);
        foreach ($items as $item) {
            $item = [
                'quantity' => (int) $item['quantity'],
                'title'    => (string) $item['title'],
                'value'    => (float) $item['value'],
            ];
            if (!$item['quantity'] && !$item['title'] && !$item['value']) {
                continue;
            }
            $this->items[] = $item;
        }

        return $this;
    }

    /**
     * @param bool $normalizeVat Some invoices have prices entered including VAT,
     *                           when set to true the function will always return values with out vat
     *
     * @return array[]
     */
    public function getItems(bool $normalizeVat = true): array
    {
        if (!$normalizeVat || !$this->preVat) {
            return $this->items; // Don't normalize value, or already normalized
        }

        $items = [];
        foreach ($this->items as $item) {
            $items[] = [
                'quantity' => $item['quantity'],
                'title'    => $item['title'],
                'value'    => $item['value'] / 1.25,
            ];
        }

        return $items;
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, ['accepted', 'giro', 'cash', 'canceled'], true);
    }

    public function getAdminLink(): string
    {
        if (null === $this->id) {
            $this->save();
        }

        return Config::get('base_url') . '/admin/faktura.php?id=' . $this->id;
    }

    public function getLink(): string
    {
        if (null === $this->id) {
            $this->save();
        }

        return Config::get('base_url') . '/betaling/' . $this->getId() . '/' . $this->getCheckid() . '/';
    }

    public function hasUnknownPrice(): bool
    {
        foreach ($this->items as $item) {
            if (!$item['value']) {
                return true;
            }
        }

        return false;
    }

    public function getNetAmount(): float
    {
        $netAmount = 0;
        foreach ($this->getItems() as $item) {
            $netAmount += $item['quantity'] * $item['value'];
        }

        return $netAmount;
    }

    public function getCheckId(): string
    {
        if (!$this->id) {
            return '';
        }

        return mb_substr(md5($this->id . Config::get('pbssalt')), 3, 5);
    }

    public function hasValidEmail(): bool
    {
        if (!$this->email || !valideMail($this->email)) {
            return false;
        }

        return true;
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
            if (!$this->shippingPostbox && (
                !$this->shippingAddress
                || ('DK' === $this->shippingCountry && !preg_match('/\s/ui', $this->shippingAddress))
            )) {
                $invalid['shippingAddress'] = true;
            }
            if (!$this->shippingPostcode) {
                $invalid['shippingPostcode'] = true;
            }
            if (!$this->shippingCity) {
                $invalid['shippingCity'] = true;
            }
            if ($this->shippingCountry) {
                $invalid['shippingCountry'] = true;
            }
        }

        return $invalid;
    }

    /**
     * Get data in array format for the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
    {
        $itemQuantities = [];
        $itemTitle = [];
        $itemValue = [];
        foreach ($this->items as $column) {
            $itemQuantities[] = $column['quantity'];
            $itemTitle[] = htmlspecialchars($column['title']);
            $itemValue[] = round($column['value'], 2);
        }

        $itemQuantities = implode('<', $itemQuantities);
        $itemTitle = implode('<', $itemTitle);
        $itemValue = implode('<', $itemValue);

        return [
            'paydate'        => ($this->timeStampPay + db()->getTimeOffset()) ? ('FROM_UNIXTIME(' . ($this->timeStampPay + db()->getTimeOffset()) . ')') : db()->eandq('0000-00-00'),
            'date'           => ($this->timeStamp + db()->getTimeOffset()) ? ('FROM_UNIXTIME(' . ($this->timeStamp + db()->getTimeOffset()) . ')') : db()->eandq('0000-00-00'),
            'quantities'     => db()->eandq($itemQuantities),
            'products'       => db()->eandq($itemTitle),
            'values'         => db()->eandq($itemValue),
            'amount'         => db()->escNum($this->amount),
            'navn'           => db()->eandq($this->name),
            'att'            => db()->eandq($this->attn),
            'adresse'        => db()->eandq($this->address),
            'postbox'        => db()->eandq($this->postbox),
            'postnr'         => db()->eandq($this->postcode),
            'by'             => db()->eandq($this->city),
            'land'           => db()->eandq($this->country),
            'email'          => db()->eandq($this->email),
            'tlf1'           => db()->eandq($this->phone1),
            'tlf2'           => db()->eandq($this->phone2),
            'altpost'        => (string) (int) $this->hasShippingAddress,
            'posttlf'        => db()->eandq($this->shippingPhone),
            'postname'       => db()->eandq($this->shippingName),
            'postatt'        => db()->eandq($this->shippingAttn),
            'postaddress'    => db()->eandq($this->shippingAddress),
            'postaddress2'   => db()->eandq($this->shippingAddress2),
            'postpostbox'    => db()->eandq($this->shippingPostbox),
            'postpostalcode' => db()->eandq($this->shippingPostcode),
            'postcity'       => db()->eandq($this->shippingCity),
            'postcountry'    => db()->eandq($this->shippingCountry),
            'note'           => db()->eandq($this->note),
            'clerk'          => db()->eandq($this->clerk),
            'status'         => db()->eandq($this->status),
            'fragt'          => db()->escNum($this->shipping),
            'momssats'       => db()->escNum($this->vat),
            'premoms'        => (string) (int) $this->preVat,
            'transferred'    => (string) (int) $this->transferred,
            'cardtype'       => db()->eandq($this->cardtype),
            'iref'           => db()->eandq($this->iref),
            'eref'           => db()->eandq($this->eref),
            'sendt'          => (string) (int) $this->sent,
            'department'     => db()->eandq($this->department),
            'enote'          => db()->eandq($this->internalNote),
        ];
    }
}
