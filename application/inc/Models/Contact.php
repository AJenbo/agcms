<?php

namespace App\Models;

use App\Services\DbService;
use App\Services\EmailService;

class Contact extends AbstractEntity
{
    /**  Table name in database. */
    public const TABLE_NAME = 'email';

    // Backed by DB

    /** @var string Name */
    private string $name = '';

    /** @var string Email */
    private string $email = '';

    /** @var string Address */
    private string $address = '';

    /** @var string Country */
    private string $country = '';

    /** @var string Postcode */
    private string $postcode = '';

    /** @var string City */
    private string $city = '';

    /** @var string Phone number */
    private string $phone1 = '';

    /** @var string Mobile phone number */
    private string $phone2 = '';

    /** @var bool Is the user subscribed to the newsletter. */
    private bool $subscribed = false;

    /** @var string[] List of newsletter topics that the user is signed up for. */
    private array $interests = [];

    private int $timestamp;

    /** @var string Client IP at moment of signup */
    private string $ip = '';

    public function __construct(array $data = [])
    {
        $this->setTimestamp($data['timestamp'] ?? time())
            ->setName($data['name'])
            ->setEmail($data['email'])
            ->setAddress($data['address'])
            ->setCountry($data['country'])
            ->setPostcode($data['postcode'])
            ->setCity($data['city'])
            ->setPhone1($data['phone1'])
            ->setPhone2($data['phone2'])
            ->setSubscribed($data['subscribed'])
            ->setInterests($data['interests'] ?? [])
            ->setIp($data['ip'])
            ->setId($data['id'] ?? null);
    }

    /**
     * Set name.
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get Name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set email.
     *
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set address.
     *
     * @return $this
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Set country.
     *
     * @return $this
     */
    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Set postcode.
     *
     * @return $this
     */
    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get postcode.
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * Set city.
     *
     * @return $this
     */
    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Set phone number.
     *
     * @return $this
     */
    public function setPhone1(string $phone1): self
    {
        $this->phone1 = $phone1;

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
     * Set mobile phone number.
     *
     * @return $this
     */
    public function setPhone2(string $phone2): self
    {
        $this->phone2 = $phone2;

        return $this;
    }

    /**
     * Get mobile phone number.
     */
    public function getPhone2(): string
    {
        return $this->phone2;
    }

    /**
     * Set newsletter subscribtion status.
     *
     * @return $this
     */
    public function setSubscribed(bool $subscribed): self
    {
        $this->subscribed = $subscribed;

        return $this;
    }

    /**
     * Get newsletter subscribtion status.
     */
    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    /**
     * Set newsletter interests.
     *
     * @param string[] $interests
     *
     * @return $this
     */
    public function setInterests(array $interests): self
    {
        $this->interests = $interests;

        return $this;
    }

    /**
     * Get interests.
     *
     * @return string[]
     */
    public function getInterests(): array
    {
        return $this->interests;
    }

    /**
     * Set created time.
     *
     * @return $this
     */
    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get creation time.
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Set client IP.
     *
     * @return $this
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get client IP.
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Check if email address is currently valid.
     */
    public function isEmailValide(): bool
    {
        return $this->email && app(EmailService::class)->valideMail($this->email);
    }

    public static function mapFromDB(array $data): array
    {
        $interests = explode('<', $data['interests']);
        $interests = array_map('html_entity_decode', $interests);

        return [
            'id'         => $data['id'],
            'timestamp'  => strtotime($data['dato']) + app(DbService::class)->getTimeOffset(),
            'name'       => $data['navn'],
            'email'      => $data['email'],
            'address'    => $data['adresse'],
            'country'    => $data['land'],
            'postcode'   => $data['post'],
            'city'       => $data['by'],
            'phone1'     => $data['tlf1'],
            'phone2'     => $data['tlf2'],
            'subscribed' => (bool)$data['kartotek'],
            'interests'  => $interests,
            'ip'         => $data['ip'],
        ];
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $this->setTimestamp(time());

        $interests = array_map('htmlspecialchars', $this->interests);
        $interests = implode('<', $interests);

        $db = app(DbService::class);

        return [
            'dato'      => $db->getNowValue(),
            'navn'      => $db->quote($this->name),
            'email'     => $db->quote($this->email),
            'adresse'   => $db->quote($this->address),
            'land'      => $db->quote($this->country),
            'post'      => $db->quote($this->postcode),
            'by'        => $db->quote($this->city),
            'tlf1'      => $db->quote($this->phone1),
            'tlf2'      => $db->quote($this->phone2),
            'kartotek'  => $db->quote((string)(int)$this->subscribed), // enum :(
            'interests' => $db->quote($interests),
            'ip'        => $db->quote($this->ip),
        ];
    }
}
