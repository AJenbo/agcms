<?php

namespace App\Models;

use App\Exceptions\InvalidInput;
use App\Services\DbService;
use App\Services\EmailService;

class Email extends AbstractEntity
{
    /**  Table name in database. */
    public const TABLE_NAME = 'emails';

    private EmailService $emailService;

    // Backed by DB

    /** @var string Subject */
    private string $subject = '';

    /** @var string HTML body */
    private string $body = '';

    /** @var string Semder name */
    private string $senderName = '';

    /** @var string Semder email address */
    private string $senderAddress = '';

    /** @var string Recipient name */
    private string $recipientName = '';

    /** @var string Recipient email address */
    private string $recipientAddress = '';

    private int $timestamp;

    public function __construct(array $data = [])
    {
        $this->emailService = app(EmailService::class);

        $this->setTimestamp($data['timestamp'] ?? time())
            ->setSubject($data['subject'])
            ->setBody($data['body'])
            ->setSenderName($data['senderName'])
            ->setSenderAddress($data['senderAddress'])
            ->setRecipientName($data['recipientName'])
            ->setRecipientAddress($data['recipientAddress'])
            ->setId($data['id'] ?? null);
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
     * @throws InvalidInput
     *
     * @return $this
     */
    public function setSubject(string $subject): self
    {
        if (!$subject) {
            throw new InvalidInput(_('Subject required.'));
        }

        $this->subject = $subject;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @throws InvalidInput
     *
     * @return $this
     */
    public function setBody(string $body): self
    {
        if (!$body) {
            throw new InvalidInput(_('Email body is required.'));
        }

        $this->body = $body;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @throws InvalidInput
     *
     * @return $this
     */
    public function setSenderName(string $senderName): self
    {
        if (!$senderName) {
            throw new InvalidInput(_('Sender name required.'));
        }

        $this->senderName = $senderName;

        return $this;
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }

    /**
     * @throws InvalidInput
     *
     * @return $this
     */
    public function setSenderAddress(string $senderAddress): self
    {
        if (!$this->emailService->valideMail($senderAddress)) {
            throw new InvalidInput(_('Sender address is not valid.'));
        }

        $this->senderAddress = $senderAddress;

        return $this;
    }

    public function getSenderAddress(): string
    {
        return $this->senderAddress;
    }

    /**
     * @throws InvalidInput
     *
     * @return $this
     */
    public function setRecipientName(string $recipientName): self
    {
        if (!$recipientName) {
            throw new InvalidInput(_('Recipient name required.'));
        }

        $this->recipientName = $recipientName;

        return $this;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    /**
     * @throws InvalidInput
     *
     * @return $this
     */
    public function setRecipientAddress(string $recipientAddress): self
    {
        if (!$this->emailService->valideMail($recipientAddress)) {
            throw new InvalidInput(_('Recipient address is not valid.'));
        }

        $this->recipientAddress = $recipientAddress;

        return $this;
    }

    public function getRecipientAddress(): string
    {
        return $this->recipientAddress;
    }

    // ORM related functions

    public static function mapFromDB(array $data): array
    {
        $from = explode('<', $data['from']);
        $senderAddress = trim($from[0]);
        $senderName = mb_substr($from[1], 0, -1);

        $to = explode('<', $data['to']);
        $recipientAddress = trim($to[0]);
        $recipientName = mb_substr($to[1], 0, -1);

        return [
            'id'               => $data['id'],
            'timestamp'        => strtotime($data['date']) + app(DbService::class)->getTimeOffset(),
            'subject'          => $data['subject'],
            'body'             => $data['body'],
            'senderName'       => $senderName,
            'senderAddress'    => $senderAddress,
            'recipientName'    => $recipientName,
            'recipientAddress' => $recipientAddress,
        ];
    }

    public function getDbArray(): array
    {
        $this->setTimestamp(time());

        $db = app(DbService::class);

        return [
            'date'    => $db->getNowValue(),
            'subject' => $db->quote($this->subject),
            'body'    => $db->quote($this->body),
            'from'    => $db->quote($this->senderAddress . '<' . $this->senderName . '>'),
            'to'      => $db->quote($this->recipientAddress . '<' . $this->recipientName . '>'),
        ];
    }
}
