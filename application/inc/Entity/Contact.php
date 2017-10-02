<?php namespace AGCMS\Entity;

class Contact extends AbstractEntity
{
    /**
     * Table name in database.
     */
    const TABLE_NAME = 'email';

    // Backed by DB
    private $name;
    private $email;
    private $address;
    private $country;
    private $postcode;
    private $city;
    private $phone1;
    private $phone2;
    private $newsletter;
    private $interests;
    private $timestamp;
    private $ip;

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTimeStamp($data['timestamp'])
            ->setName($data['name'])
            ->setEmail($data['email'])
            ->setAddress($data['address'])
            ->setCountry($data['country'])
            ->setPostcode($data['postcode'])
            ->setCity($data['city'])
            ->setPhone1($data['phone1'])
            ->setPhone2($data['phone2'])
            ->setNewsletter($data['newsletter'])
            ->setInterests($data['interests'])
            ->setIp($data['ip']);
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setPhone1(string $phone1): self
    {
        $this->phone1 = $phone1;

        return $this;
    }

    public function getPhone1(): string
    {
        return $this->phone1;
    }

    public function setPhone2(string $phone2): self
    {
        $this->phone2 = $phone2;

        return $this;
    }

    public function getPhone2(): string
    {
        return $this->phone2;
    }

    public function setNewsletter(bool $newsletter): self
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    public function getNewsletter(): bool
    {
        return $this->newsletter;
    }

    public function setInterests(string $interests): self
    {
        $this->interests = $interests;

        return $this;
    }

    public function getInterests(): string
    {
        return $this->interests;
    }

    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function isEmailValide(): bool
    {
        return $this->email && valideMail($this->email);
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
        return [
            'id'         => $data['id'],
            'timestamp'  => strtotime($data['dato']) + db()->getTimeOffset(),
            'name'       => $data['navn'],
            'email'      => $data['email'],
            'address'    => $data['adresse'],
            'country'    => $data['land'],
            'postcode'   => $data['post'],
            'city'       => $data['by'],
            'phone1'     => $data['tlf1'],
            'phone2'     => $data['tlf2'],
            'newsletter' => (bool) $data['kartotek'],
            'interests'  => $data['interests'],
            'ip'         => $data['ip'],
        ];
    }

    // ORM related functions

    /**
     * Get data in array format for the database.
     *
     * @return array
     */
    public function getDbArray(): array
    {
        $this->setTimeStamp(time());

        return [
            'dato'      => "NOW()",
            'navn'      => db()->eandq($this->name),
            'email'     => db()->eandq($this->email),
            'adresse'   => db()->eandq($this->address),
            'land'      => db()->eandq($this->country),
            'post'      => db()->eandq($this->postcode),
            'by'        => db()->eandq($this->city),
            'tlf1'      => db()->eandq($this->phone1),
            'tlf2'      => db()->eandq($this->phone2),
            'kartotek'  => db()->eandq((int) $this->newsletter), // enum :(
            'interests' => db()->eandq($this->interests),
            'ip'        => db()->eandq($this->ip),
        ];
    }
}
