<?php

/**
 * A helper class for communication with ePay
 *
 * See http://www.betalingsterminal.no/Netthandel-forside/Teknisk-veiledning/API/ for
 * a description of the returned objects
 */

class EpaymentAdminService
{
    private $merchantId;
    private $password;
    private $amount;
    private $soapClient;
    private $id = 0;
    private $Authorized = false;
    private $Error = false;
    private $Annulled = false;
    private $AmountCaptured = 0;

    /**
     * Setup the class variables for initialization
     *
     * @param string $merchantId Id provided by PBS identifying the shop
     * @param string $orderId    The order id
     */
    public function __construct(string $merchantId, string $password, string $orderId)
    {
        $this->merchantId = $merchantId;
        $this->password = $password;
        $this->soapClient = new SoapClient(
            'https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL'
        );

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
        if (empty($response->transactionInformationAry) || !(array) $response->transactionInformationAry) {
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
        }
        if (empty($response->transactionInformationAry) || !(array) $response->transactionInformationAry) {
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
        }

        if (!empty($response->transactionInformationAry) && (array) $response->transactionInformationAry) {
            $info = $response->transactionInformationAry->TransactionInformationType;

            $this->id = $info->transactionid;
            $this->amount = (int) $info->authamount;

            if ($info->status == 'PAYMENT_NEW') {
                $this->Authorized = true;
            } elseif ($info->status == 'PAYMENT_CAPTURED') {
                $this->AmountCaptured = (int) $info->capturedamount;
            } elseif ($info->status == 'PAYMENT_DELETED') {
                $this->Annulled = true;
            }
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->Authorized;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->Error;
    }

    /**
     * @return bool
     */
    public function isAnnulled(): bool
    {
        return $this->Annulled;
    }

    /**
     * @return int
     */
    public function getAmountCaptured(): int
    {
        return $this->AmountCaptured;
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
                'transactionid' => $this->id,
                'epayresponse' => true,
            ]
        );

        if (!$response->deleteResult) {
            $this->Error = true;
            return false;
        }

        $this->Authorized = false;
        $this->Annulled = true;

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

        if ($this->AmountCaptured) {
            return true; // TODO can we not capture multiple times, should substract it form $amount?
        }

        if ($this->amount < $amount || !$this->Authorized) {
            return false;
        }

        $response = $this->soapClient->capture(
            [
                'pwd' => $this->password,
                'merchantnumber' => $this->merchantId,
                'transactionid' => $this->id,
                'amount' => $amount,
                'epayresponse' => true,
                'pbsResponse' => true,
            ]
        );

        if (!$response->captureResult) {
            $this->Error = true;
            return false;
        }

        $this->AmountCaptured = $amount;
        $this->Authorized = false;

        return true;
    }
}
