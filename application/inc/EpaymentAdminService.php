<?php

/**
 * A helper class for communication with ePay
 *
 * See http://www.betalingsterminal.no/Netthandel-forside/Teknisk-veiledning/API/ for
 * a description of the returned objects
 */

class EpaymentAdminService
{
    /**
     * Shops merchant id
     */
    private $merchantId;

    /**
     * Service password
     */
    private $password;

    /**
     * Service connection
     */
    private $soapClient;

    /**
     * Id of transaction
     */
    private $transactionId = 0;

    /**
     * Transaction ammount
     */
    private $amount;

    /**
     * Amount that was transfered to the shop
     */
    private $amountCaptured = 0;

    /**
     * Is the transaction authorized and ready to transfer the amount
     */
    private $authorized = false;

    /**
     * Has the transaction been cancled
     */
    private $annulled = false;

    /**
     * Did an error occure on the last action
     */
    private $error = false;

    /**
     * Setup the class variables for initialization
     *
     * @param string $merchantId Id provided by PBS identifying the shop
     * @param string $password   Password for service
     * @param string $orderId    The order id
     */
    public function __construct(string $merchantId, string $password, string $orderId)
    {
        $this->merchantId = $merchantId;
        $this->password = $password;
        $this->soapClient = new SoapClient(
            'https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL'
        );

        $transactionData = $this->getTransactionData($orderId);

        if (!$transactionData) {
            return;
        }

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
     * Fetch the transation data
     *
     * @param string $orderId Shop order id
     *
     * @return stdClass
     */
    private function getTransactionData(string $orderId)
    {
        $response = $this->soapClient->gettransactionlist(
            [
                'pwd' => $this->password,
                'merchantnumber' => $this->merchantId,
                'searchorderid' => $orderId,
                'status' => 'PAYMENT_NEW',
                'searchdatestart' => '2014-06-19T00:00:00+02:00',
                'searchdateend' => date('c'),
                'epayresponse' => true,
            ]
        );
        if (!empty($response->transactionInformationAry) && (array) $response->transactionInformationAry) {
            return $response->transactionInformationAry->TransactionInformationType;
        }

        $response = $this->soapClient->gettransactionlist(
            [
                'pwd' => $this->password,
                'merchantnumber' => $this->merchantId,
                'searchorderid' => $orderId,
                'status' => 'PAYMENT_CAPTURED',
                'searchdatestart' => '2014-06-19T00:00:00+02:00',
                'searchdateend' => date('c'),
                'epayresponse' => true,
            ]
        );
        if (!empty($response->transactionInformationAry) && (array) $response->transactionInformationAry) {
            return $response->transactionInformationAry->TransactionInformationType;
        }

        $response = $this->soapClient->gettransactionlist(
            [
                'pwd' => $this->password,
                'merchantnumber' => $this->merchantId,
                'searchorderid' => $orderId,
                'status' => 'PAYMENT_DELETED',
                'searchdatestart' => '2014-06-19T00:00:00+02:00',
                'searchdateend' => date('c'),
                'epayresponse' => true,
            ]
        );
        if (!empty($response->transactionInformationAry) && (array) $response->transactionInformationAry) {
            return $response->transactionInformationAry->TransactionInformationType;
        }

        return null;
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
        $response = $this->soapClient->delete(
            [
                'pwd' => $this->password,
                'merchantnumber' => $this->merchantId,
                'transactionid' => $this->transactionId,
                'epayresponse' => true,
            ]
        );

        if (!$response->deleteResult) {
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

        $response = $this->soapClient->capture(
            [
                'pwd' => $this->password,
                'merchantnumber' => $this->merchantId,
                'transactionid' => $this->transactionId,
                'amount' => $amount,
                'epayresponse' => true,
                'pbsResponse' => true,
            ]
        );

        if (!$response->captureResult) {
            $this->error = true;
            return false;
        }

        $this->amountCaptured = $amount;
        $this->authorized = false;

        return true;
    }
}
