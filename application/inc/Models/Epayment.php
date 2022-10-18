<?php

namespace App\Models;

use App\Services\EpaymentService;
use stdClass;

class Epayment
{
    /**
     * The manager for handeling service communication.
     *
     * @var EpaymentService
     */
    private $service;

    /**
     * Id of transaction.
     *
     * @var int
     */
    private $transactionId = 0;

    /**
     * Transaction ammount.
     *
     * @var int
     */
    private $amount = 0;

    /**
     * Amount that was transfered to the shop.
     *
     * @var int
     */
    private $amountCaptured = 0;

    /**
     * Is the transaction authorized and ready to transfer the amount.
     *
     * @var bool
     */
    private $authorized = false;

    /**
     * Has the transaction been cancled.
     *
     * @var bool
     */
    private $annulled = false;

    /**
     * Setup the class variables for initialization.
     *
     * @param EpaymentService $service The manager for handeling service communication
     */
    public function __construct(EpaymentService $service, stdClass $transactionData)
    {
        $this->service = $service;

        $this->transactionId = $transactionData->transactionid;
        $this->amount = (int) $transactionData->authamount;

        if ('PAYMENT_NEW' === $transactionData->status) {
            $this->authorized = true;
        } elseif ('PAYMENT_CAPTURED' === $transactionData->status) {
            $this->amountCaptured = (int) $transactionData->capturedamount;
        } elseif ('PAYMENT_DELETED' === $transactionData->status) {
            $this->annulled = true;
        }
    }

    /**
     * Get transaction id.
     */
    public function getId(): int
    {
        return $this->transactionId;
    }

    /**
     * Is the transaction ready to be captured.
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * Has the transaction been cancled.
     */
    public function isAnnulled(): bool
    {
        return $this->annulled;
    }

    /**
     * Transfer an amount to the shop.
     */
    public function getAmountCaptured(): int
    {
        return $this->amountCaptured;
    }

    /**
     * Canncels a payment transation.
     */
    public function annul(): bool
    {
        if ($this->annulled) {
            return true;
        }

        $success = $this->service->annul($this);
        if (!$success) {
            return false;
        }

        $this->authorized = false;
        $this->annulled = true;

        return true;
    }

    /**
     * Confirm the transation and draw the amount from the users account.
     *
     * @todo support multiple partial captures
     *
     * @param null|int $amount The amount to draw from the customers account
     */
    public function confirm(int $amount = null): bool
    {
        $amount = $amount ?? $this->amount;

        if ($this->amountCaptured) {
            return true;
        }

        if ($this->amount < $amount || !$this->authorized) {
            return false;
        }

        return $this->doCapture($amount);
    }

    /**
     * Send the actual transaction request.
     */
    private function doCapture(int $amount): bool
    {
        $success = $this->service->confirm($this, $amount);
        if (!$success) {
            return false;
        }

        $this->amountCaptured = $amount;
        $this->authorized = false;

        return true;
    }
}
