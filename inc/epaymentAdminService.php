<?php

/**
 * Implement epaymentAdminService
 */

/**
 * A helper class for communication with ePay
 *
 * See http://www.betalingsterminal.no/Netthandel-forside/Teknisk-veiledning/API/ for
 * a description of the returned objects
 */

class epaymentAdminService
{
    private $_merchantId;
    private $_amount;
    private $_soapClient;
    public $id = 0;
    public $Authorized = false;
    public $Error = false;
    public $Annulled = false;
    public $AmountCaptured = 0;

    /**
     * Setup the class variables for initialization
     *
     * @param string $merchantId Id provided by PBS identifying the shop
     * @param string $orderId    The order id
     */
    function __construct($merchantId, $orderId)
    {
        $this->_merchantId = $merchantId;
        $this->_soapClient = new SoapClient(
            'https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL'
        );

        $response = $this->_soapClient->gettransactionlist(
            array(
                'pwd' => $GLOBALS['_config']['pbspwd'],
                'merchantnumber' => $this->_merchantId,
                'searchorderid' => $orderId,
                'status' => 'PAYMENT_NEW',
                'searchdatestart' => '2014-06-19T00:00:00+02:00',
                'searchdateend' => date('c'),
                'epayresponse' => true,
            )
        );

        if (!(array) $response->transactionInformationAry) {
            $response = $this->_soapClient->gettransactionlist(
                array(
                    'pwd' => $GLOBALS['_config']['pbspwd'],
                    'merchantnumber' => $this->_merchantId,
                    'searchorderid' => $orderId,
                    'status' => 'PAYMENT_CAPTURED',
                    'searchdatestart' => '2014-06-19T00:00:00+02:00',
                    'searchdateend' => date('c'),
                    'epayresponse' => true,
                )
            );
        }
        if (!(array) $response->transactionInformationAry) {
            $response = $this->_soapClient->gettransactionlist(
                array(
                    'pwd' => $GLOBALS['_config']['pbspwd'],
                    'merchantnumber' => $this->_merchantId,
                    'searchorderid' => $orderId,
                    'status' => 'PAYMENT_DELETED',
                    'searchdatestart' => '2014-06-19T00:00:00+02:00',
                    'searchdateend' => date('c'),
                    'epayresponse' => true,
                )
            );
        }

        if ((array) $response->transactionInformationAry) {
            $info = $response->transactionInformationAry->TransactionInformationType;

            $this->id = $info->transactionid;
            $this->_amount = (int) $info->authamount;

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
     * Canncels a payment transation
     *
     * @param int $transactionId The identifyer for the transation
     *
     * @return bool
     */
    function annul()
    {
        $response = $this->_soapClient->delete(
            array(
                'pwd' => $GLOBALS['_config']['pbspwd'],
                'merchantnumber' => $this->_merchantId,
                'transactionid' => $this->id,
                'epayresponse' => true,
            )
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
    function confirm($amount = null)
    {
        if (!$amount) {
            $amount = $this->_amount;
        }

        if ($this->AmountCaptured) {
            return true;
        }

        if ($this->_amount < $amount || !$this->Authorized) {
            return false;
        }

        $response = $this->_soapClient->capture(
            array(
                'pwd' => $GLOBALS['_config']['pbspwd'],
                'merchantnumber' => $this->_merchantId,
                'transactionid' => $this->id,
                'amount' => $amount,
                'epayresponse' => true,
                'pbsResponse' => true,
            )
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

