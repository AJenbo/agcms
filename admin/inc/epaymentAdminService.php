<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';

/* New Netaxept implementation
 */

class epaymentAdminService
{
    public $Merchant_id;
    public $Token;
    private $_soapClient;

    /**
     * Setup the class variables for initialization
     *
     * @param string   $Merchant_id id provided by PBS identifying the shop
     * @param string $token the password provided by PBS
     * @param bool   $test  NOT IMPLEMENTED!
     */
    function __construct($merchant_id, $token, $test = false)
    {
        $this->Merchant_id = $merchant_id;
        $this->Token = $token;
        $this->_soapClient = new SoapClient('https://epayment.nets.eu/Netaxept.svc?wsdl');
    }

    /**
     * Canncels a payment transation
     * see http://www.betalingsterminal.no/Netthandel-forside/Teknisk-veiledning/API/Process/ - see ANNUL section
     * @param int $Transaction_id the epayment identifyer for the transation
     *
     * @return \ref AdminResponse array
     */
    function annul($Transaction_id)
    {
        $annulRequest = array('Description' => '', 'Operation' => 'ANNUL', 'TransactionAmount' => '', 'TransactionId' => $Transaction_id, 'TransactionReconRef' => '');

        $request = array
        (
            "token"       => $this->Token,
            "merchantId"  => $this->Merchant_id,
            "request"     => $annulRequest
        );

        $result = $this->_soapClient->__call('Process' , array('parameters'=>$request));
        return (array) $result->ProcessResult;
    }

    /**
     * Confirm the transation and draw the amount from the users account
     * see http://www.betalingsterminal.no/Netthandel-forside/Teknisk-veiledning/API/Process/
     * @param int    $Transaction_id the epayment identifyer for the transation
     * @param string $Delivery_date  the expected delivery date of the goods, Format 'yyyymmdd'
     *
     * @return \ref AdminResponse array
     */
    function confirm($Transaction_id, $Delivery_date)
    {
        $processRequest = array('Description' => '', 'Operation' => 'CAPTURE', 'TransactionAmount' => '', 'TransactionId' => $Transaction_id, 'TransactionReconRef' => '');

        $request = array
        (
            "token"       => $this->Token,
            "merchantId"  => $this->Merchant_id,
            "request"     => $processRequest
        );

        $result = $this->_soapClient->__call('Process' , array('parameters'=>$request));
        return (array) $result->ProcessResult;
    }

    /**
     * query for the status of a transations using the Customer_refno id
     * see http://www.betalingsterminal.no/Netthandel-forside/Teknisk-veiledning/API/Query/
     * @return \ref AdminResponse array
     */
    function query($Customer_refno)
    {
        $queryRequest = array('TransactionId' => $Customer_refno);

        $request = array
        (
            "token"       => $this->Token,
            "merchantId"  => $this->Merchant_id,
            "request"     => $queryRequest
        );

        $result = $this->_soapClient->__call('Query' , array('parameters'=>$request));
        return (array) $result->QueryResult;
    }
}

