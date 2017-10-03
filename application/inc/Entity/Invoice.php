<?php namespace AGCMS\Entity;

use AGCMS\Config;

class Invoice extends AbstractEntity
{
    /**
     * Table name in database
     */
    const TABLE_NAME = 'fakturas';

    // Backed by DB
    private $timeStamp;
    private $timeStampPay;
    private $amount;
    private $name;
    private $att;
    private $address;
    private $postbox;
    private $postcode;
    private $city;
    private $country;
    private $email;
    private $phone1;
    private $phone2;
    private $hasShippingAddress;
    private $shippingPhone;
    private $shippingName;
    private $shippingAtt;
    private $shippingAddress;
    private $shippingAddress2;
    private $shippingPostbox;
    private $shippingPostcode;
    private $shippingCity;
    private $shippingCountry;
    private $note;
    private $clerk;
    private $status;
    private $discount;
    private $shipping;
    private $vat;
    private $preVat;
    private $transferred;
    private $cardtype;
    private $iref;
    private $eref;
    private $sent;
    private $department;
    private $enote;
    // Dynamic
    private $items;

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

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount(): string
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

    public function setAtt(string $att): self
    {
        $this->att = trim($att);
        return $this;
    }

    public function getAtt(): string
    {
        return $this->att;
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

    public function setShippingAtt(string $shippingAtt): self
    {
        $this->shippingAtt = trim($shippingAtt);
        return $this;
    }

    public function getShippingAtt(): string
    {
        return $this->shippingAtt;
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

    public function setDiscount(string $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function getDiscount(): string
    {
        return $this->discount;
    }

    public function setShipping(string $shipping): self
    {
        $this->shipping = $shipping;
        return $this;
    }

    public function getShipping(): string
    {
        return $this->shipping;
    }

    public function setVat(string $vat): self
    {
        $this->vat = $vat;
        return $this;
    }

    public function getVat(): string
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

    public function setEnote(string $enote): self
    {
        $this->enote = trim($enote);
        return $this;
    }

    public function getEnote(): string
    {
        return $this->enote;
    }

    /**
     * Construct the entity
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setItemData($data['item_data'])
            ->setHasShippingAddress($data['has_shipping_address'] ?? false)
            ->setTimeStamp($data['timestamp'] ?? 0)
            ->setTimeStampPay($data['timestamp'] ?? 0)
            ->setAmount($data['amount'] ?? '0.00')
            ->setName($data['name'])
            ->setAtt($data['att'])
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
            ->setShippingAtt($data['shipping_att'])
            ->setShippingAddress($data['shipping_address'])
            ->setShippingAddress2($data['shipping_address2'])
            ->setShippingPostbox($data['shipping_postbox'])
            ->setShippingPostcode($data['shipping_postcode'])
            ->setShippingCity($data['shipping_city'])
            ->setShippingCountry($data['shipping_country'] ?? 'DK')
            ->setNote($data['note'])
            ->setClerk($data['clerk'] ?? '')
            ->setStatus($data['status'] ?? 'new')
            ->setDiscount($data['discount'] ?? '0.00')
            ->setShipping($data['shipping'] ?? '0.00')
            ->setVat($data['vat'] ?? '0.25')
            ->setPreVat($data['pre_vat'] ?? true)
            ->setTransferred($data['transferred'] ?? false)
            ->setCardtype($data['cardtype'] ?? '')
            ->setIref($data['iref'] ?? '')
            ->setEref($data['eref'] ?? '')
            ->setSent($data['sent'] ?? false)
            ->setDepartment($data['department'] ?? '')
            ->setEnote($data['enote'] ?? '');
    }

    /**
     * Map data from DB table to entity
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
        $itemValue = array_map('intval', $itemValue);
        $itemTitle = explode('<', $data['products']);
        $itemTitle = array_map('html_entity_decode', $itemTitle);

        $items = [];
        foreach ($itemTitle as $key => $title) {
            $items[] = [
                'title'    => $title,
                'value'    => $itemValue[$key] ?? 0,
                'quantity' => $itemQuantities[$key] ?? 0,
            ];
        }
        $items = json_encode($items);

        return [
            'id'                   => $data['id'],
            'item_data'            => $items,
            'has_shipping_address' => (bool) $data['altpost'],
            'timestamp'            => strtotime($data['date']) + db()->getTimeOffset(),
            'timestamp_pay'        => strtotime($data['paydate']) + db()->getTimeOffset(),
            'amount'               => (int) $data['amount'],
            'name'                 => $data['navn'],
            'att'                  => $data['att'],
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
            'shipping_att'         => $data['postatt'],
            'shipping_address'     => $data['postaddress'],
            'shipping_address2'    => $data['postaddress2'],
            'shipping_postbox'     => $data['postpostbox'],
            'shipping_postcode'    => $data['postpostalcode'],
            'shipping_city'        => $data['postcity'],
            'shipping_country'     => $data['postcountry'],
            'note'                 => $data['note'],
            'clerk'                => $data['clerk'],
            'status'               => $data['status'],
            'discount'             => $data['discount'],
            'shipping'             => $data['fragt'],
            'vat'                  => $data['momssats'],
            'pre_vat'              => (bool) $data['premoms'],
            'transferred'          => (bool) $data['transferred'],
            'cardtype'             => $data['cardtype'],
            'iref'                 => $data['iref'],
            'eref'                 => $data['eref'],
            'sent'                 => (bool) $data['sendt'],
            'department'           => $data['department'],
            'enote'                => $data['enote'],
        ];
    }

    /**
     * Set the item data
     *
     * @param string $itemData Array encoded as JSON
     *
     * @return self
     */
    public function setItemData(string $itemData): self
    {
        $this->items = json_decode($itemData, true);
        return $this;
    }

    /**
     * @param bool $normalizeVat Some invoices have prices entered including VAT,
     * when set to true the function will always return values with out vat
     *
     * @return array
     */
    public function getItems(bool $normalizeVat = true): array
    {
        if (!$normalizeVat || !$this->preVat) {
            return $this->items; // Don't normalize value, or already normalized
        }

        $items = [];
        foreach ($this->items as $item) {
            $items[] = [
                'title' => $item['title'],
                'quantity' => $item['quantity'],
                'value' => $item['value'] / 1.25,
            ];
        }

        return $items;
    }

    public function getAdminLink(): string
    {
        if ($this->id === null) {
            $this->save();
        }

        return Config::get('base_url') . '/admin/faktura.php?id=' . $this->id;
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
        foreach ($this->items as $item) {
            $netAmount += $item['value'] * $item['quantity'];
        }

        if ($this->preVat) {
            return $netAmount / 1.25;
        }

        return $netAmount;
    }

    public function getCheckId()
    {
        if (!$this->id) {
            return '';
        }

        return mb_substr(md5($this->id . Config::get('pbssalt')), 3, 5);
    }

    /**
     * Checks that all nessesery contact information has been filled out correctly
     *
     * @return array Key with bool true for each faild feald
     */
    function getInvalid(): array
    {
        $invalid = [];

        if (!$this->email || !valideMail($this->email)) {
            $invalid['email'] = true;
        }
        if (!$this->name) {
            $invalid['name'] = true;
        }
        if (!$this->country) {
            $invalid['country'] = true;
        }
        if (!$this->postbox
            && (!$this->address || ($this->country === 'DK' && !preg_match('/\s/ui', $this->address)))
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
                || ($this->shippingCountry === 'DK' && !preg_match('/\s/ui', $this->shippingAddress))
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
     * Get data in array format for the database
     *
     * @return array
     */
    public function getDbArray(): array
    {
        $itemQuantities = [];
        $itemTitle = [];
        $itemValue = [];
        foreach ($this->items as $column) {
            $itemQuantities[] = $column['quantity'];
            $itemTitle[] = $column['title'];
            $itemValue[] = $column['value'];
        }

        $itemQuantities = implode('<', $itemQuantities);
        $itemTitle = array_map('htmlspecialchars', $itemTitle);
        $itemTitle = implode('<', $itemTitle);
        $itemValue = implode('<', $itemValue);

        $this->setTimeStamp(time());
        return [
            'paydate'        => $this->timeStampPay ? ("UNIX_TIMESTAMP(" . $this->timeStampPay . ")") : db()->eandq('0000-00-00'),
            'date'           => "NOW()",
            'quantities'     => db()->eandq($itemQuantities),
            'products'       => db()->eandq($itemTitle),
            'values'         => db()->eandq($itemValue),
            'amount'         => db()->eandq($this->amount),
            'navn'           => db()->eandq($this->name),
            'att'            => db()->eandq($this->att),
            'postbox'        => db()->eandq($this->postbox),
            'postnr'         => db()->eandq($this->postcode),
            'by'             => db()->eandq($this->city),
            'land'           => db()->eandq($this->country),
            'email'          => db()->eandq($this->email),
            'tlf1'           => db()->eandq($this->phone1),
            'tlf2'           => db()->eandq($this->phone2),
            'altpost'        => (int) $this->hasShippingAddress,
            'posttlf'        => db()->eandq($this->shippingPhone),
            'postname'       => db()->eandq($this->shippingName),
            'postatt'        => db()->eandq($this->shippingAtt),
            'postaddress'    => db()->eandq($this->shippingAddress),
            'postaddress2'   => db()->eandq($this->shippingAddress2),
            'postpostbox'    => db()->eandq($this->shippingPostbox),
            'postpostalcode' => db()->eandq($this->shippingPostcode),
            'postcity'       => db()->eandq($this->shippingCity),
            'postcountry'    => db()->eandq($this->shippingCountry),
            'note'           => db()->eandq($this->note),
            'clerk'          => db()->eandq($this->clerk),
            'status'         => db()->eandq($this->status),
            'discount'       => db()->eandq($this->discount),
            'fragt'          => db()->eandq($this->shipping),
            'momssats'       => db()->eandq($this->vat),
            'premoms'        => (int) $this->preVat,
            'transferred'    => (int) $this->transferred,
            'cardtype'       => db()->eandq($this->cardtype),
            'iref'           => db()->eandq($this->iref),
            'eref'           => db()->eandq($this->eref),
            'sendt'          => (int) $this->sent,
            'department'     => db()->eandq($this->department),
            'enote'          => db()->eandq($this->enote),
        ];
    }
}
