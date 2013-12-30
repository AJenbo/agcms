<?php

/**
 * Implement epaymentAdminService
 */

/**
 * A helper class for communication with PBS Netaxept web service
 *
 * See http://www.betalingsterminal.no/Netthandel-forside/Teknisk-veiledning/API/ for
 * a description of the returned objects
 */

class epaymentAdminService
{
    private $_merchantId;
    private $_token;
    private $_soapClient;

    /**
     * Setup the class variables for initialization
     *
     * @param string $merchantId Id provided by PBS identifying the shop
     * @param string $token       The password provided by PBS
     */
    function __construct($merchantId, $token)
    {
        $this->_merchantId = $merchantId;
        $this->_token = $token;
        $this->_soapClient = new SoapClient('https://epayment.nets.eu/Netaxept.svc?wsdl');
    }

    /**
     * Preformce the soap call and handle standart parameteres
     *
     * @param string $call    The type of call to preforme
     * @param array  $request The request parameteres
     *
     * @return Object
     */
    function _call($call, array $request)
    {
        return $this->_soapClient->__call(
            $call,
            array(
                array(
                    'token' => $this->_token,
                    'merchantId' => $this->_merchantId,
                    'request' => $request
                )
            )
        );
    }

    /**
     * Register a request
     *
     * @param int    $transactionId The identifyer for the transation
     * @param int    $amount        The amount in cents
     * @param string $redirectUrl   The url to send the customer to afterwards
     * @param string $currencyCode  The currency type for the amount (DKK)
     * @param string $language      The languange to display the payment gateway in
     *
     * @return \ref AdminResponse array
     */
    function register($transactionId, $amount, $redirectUrl,
        $currencyCode = 'DKK', $language = 'da_DK'
    ) {
        $request = new stdClass;
        $request->Environment = new stdClass;
        $request->Environment->WebServicePlatform = 'PHP5';
        $request->Order = new stdClass;
        $request->Order->Amount = $amount;
        $request->Order->CurrencyCode = $currencyCode;
        $request->Order->Force3DSecure = false;
        $request->Order->OrderNumber = $transactionId;
        $request->Terminal = new stdClass;
        $request->Terminal->Language = $language;
        $request->Terminal->RedirectOnError = true;
        $request->Terminal->RedirectUrl = $redirectUrl;
        $request->TransactionId = $transactionId;
        return $this->_call('Register', $request)->RegisterResult;
    }

    /**
     * Canncels a payment transation
     *
     * @param int $transactionId The identifyer for the transation
     *
     * @return \ref AdminResponse array
     */
    function annul($transactionId)
    {
        $request = new stdClass;
        $request->Operation = 'ANNUL';
        $request->TransactionId = $transactionId;
        return $this->_call('Process', $request)->ProcessResult;
    }

    /**
     * Confirm the transation and draw the amount from the users account
     *
     * @param int $transactionId The identifyer for the transation
     *
     * @return \ref AdminResponse array
     */
    function confirm($transactionId)
    {
        $request = new stdClass;
        $request->Operation = 'CAPTURE';
        $request->TransactionId = $transactionId;
        return $this->_call('Process', $request)->ProcessResult;
    }

    /**
     * Query for the status of a transations
     *
     * @param int $transactionId The identifyer for the transation
     *
     * @return \ref AdminResponse array
     */
    function query($transactionId)
    {
        $request = new stdClass;
        $request->TransactionId = $transactionId;
        return $this->_call('Query', $request)->QueryResult;
    }
}

