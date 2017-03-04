<?php

class Epayment
{
    /**
     * The manager for handeling service communication
     * @var EpaymentAdminService
     */
    private $service;

    /**
     * Id of transaction
     * @var int
     */
    private $transactionId = 0;

    /**
     * Transaction ammount
     * @var int
     */
    private $amount = 0;

    /**
     * Amount that was transfered to the shop
     * @var int
     */
    private $amountCaptured = 0;

    /**
     * Is the transaction authorized and ready to transfer the amount
     * @var bool
     */
    private $authorized = false;

    /**
     * Has the transaction been cancled
     * @var bool
     */
    private $annulled = false;

    /**
     * Did an error occure on the last action
     * @var bool
     */
    private $error = false;

    /**
     * Setup the class variables for initialization
     *
     * @param EpaymentAdminService $service The manager for handeling service communication
     * @param stdClass $transactionData
     */
    public function __construct(EpaymentAdminService $service, stdClass $transactionData)
    {
        $this->service = $service;

        $this->transactionId = $transactionData->transactionid;
        $this->amount = (int) $transactionData->authamount;

        if ($transactionData->status == 'PAYMENT_NEW') {
            $this->authorized = true;
        } elseif ($transactionData->status == 'PAYMENT_CAPTURED') {
            $this->amountCaptured = (int) $transactionData->capturedamount;
        } elseif ($transactionData->status == 'PAYMENT_DELETED') {
            $this->annulled = true;
        }
    }

    /**
     * Get transaction id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->transactionId;
    }

    /**
     * Is the transaction ready to be captured
     *
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * Did an error occure on the last action
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->error;
    }

    /**
     * Has the transaction been cancled
     *
     * @return bool
     */
    public function isAnnulled(): bool
    {
        return $this->annulled;
    }

    /**
     * Transfer an amount to the shop
     *
     * @return int
     */
    public function getAmountCaptured(): int
    {
        return $this->amountCaptured;
    }

    /**
     * Canncels a payment transation
     *
     * @param int $transactionId The identifyer for the transation
     *
     * @return bool
     */
    public function annul(): bool
    {
        if ($this->annulled) {
            return true;
        }

        $success = $this->service->annul($this);
        if (!$success) {
            $this->error = true;
            return false;
        }

        $this->authorized = false;
        $this->annulled = true;

        return true;
    }

    /**
     * Confirm the transation and draw the amount from the users account
     *
     * @param int $amount The amount to draw from the customers account
     *
     * @return bool
     */
    public function confirm(int $amount = null): bool
    {
        if (!$amount) {
            $amount = $this->amount;
        }

        if ($this->amountCaptured) {
            return true; // TODO can we not capture multiple times, should substract it form $amount?
        }

        if ($this->amount < $amount || !$this->authorized) {
            return false;
        }

        $success = $this->service->confirm($this, $amount);
        if (!$success) {
            $this->error = true;
            return false;
        }

        $this->amountCaptured = $amount;
        $this->authorized = false;

        return true;
    }
}
