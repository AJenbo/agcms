<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';

/**
 * This Class is for simplyfiying sending querys to PBS epayment admin web service
 * This class handels all the Soap comunication and standadises the naming of variables
 * The naming of variables is inconsistant. The naming convention differes from the web admin service, it's documentation and the client side.
 *
 * \author Anders Jenbo
 * \date 29/06/2009
 *
 * The current version has been released under the MIT License.
 */

//The following is the responce returned by the functions of the class
/**
 * @section AdminResponse
 * AdminResponse is what is returned by the PBS epayment admin web service in the form of an array
 * This information referes to the documentation for the Webservice Admin API 1.1
 *
 * - Status A if success else E
 * - \ref StatusCode
 * - Customerrefno An unique reference number defined by the merchant
 * - Comment Transaction comment
 * - TransactionId An unique reference number defined by Auriga ePayment
 * - Mac A MD5 of all parameters and secret word.
 *
 * In addition to theas the following responces may also be returned depending on the action
 *
 * - AuthCode The Authcode
 * - PaymentMethod Payment method. Eg, KORTINSE or KORTMODK.
 * - CardType Card brand used in transaction. Eg.VISA
 * - AuthorizedAmount The amount which have been authorized defined in hundreds of the currency (i.e. cents)
 * - FeeAmount Fee amount on CardFee, defined in hundreds of the currency (i.e. cents)
 * - RiskScore Risk assessment figure for a card payment, two decimal points. The punctuation to be used is a full stop. Only permitted when Risk Assessment is activated for the Merchant, as well as when the Country parameter has been sent with the call.
 * - ThreeDSecure 3-D Secure status for KORTINSE and KORTABSE transactions.	Y = Yes, payment guarantee applies in accordance with VISA/MasterCard regulations. N = No, no payment guarantee NA = not a 3-D Secure transaction
 * - BatchId Auriga ePayment's Bundle Number for acquired KORTINSE and KORTABSE transactions
 *
 * As the system is expanded, additional parameters may be added.
 *
 * @section StatusCode
 * The StatusCode reflects the status of the transction and action
 *
 * See the PDF
 */

class epaymentAdminService
{
    private $_soapClient;
    public $Merchant_id;
    public $Secret_word;

    /**
     * Setup the class variables for initialization
     *
     * @param TODO   $Merchant_id id provided by PBS identifying the shop
     * @param string $Secret_word the password provided by PBS
     * @param bool   $test        runs aplication in test mode
     */
    function __construct($Merchant_id, $Secret_word, $test = false)
    {
        $this->Merchant_id = $Merchant_id;
        $this->Secret_word = $Secret_word;
        if ($test) {
            $this->_soapClient = new SoapClient('https://test-epayment.auriganet.eu/webservice/AdminService?WSDL');
        } else {
            $this->_soapClient = new SoapClient('https://epayment.auriganet.eu/webservice/AdminService?WSDL');
        }
    }

    /**
     * Canncels a payment transation
     *
     * @param int $Transaction_id the epayment identifyer for the transation
     *
     * @return \ref AdminResponse array
     */
    function annul($Transaction_id)
    {
        $params['merchantid'] = $this->Merchant_id;
        $params['transactionid'] = $Transaction_id;
        $params['mac'] = md5(implode('', $params).$this->Secret_word);
        $result = $this->_soapClient->annul($params);
        return (array) $result->return;
    }

    /**
     * Confirm the transation and draw the amount from the users account
     *
     * @param int    $Transaction_id the epayment identifyer for the transation
     * @param string $Delivery_date  the expected delivery date of the goods, Format 'yyyymmdd'
     *
     * @return \ref AdminResponse array
     */
    function confirm($Transaction_id, $Delivery_date)
    {
        $params['merchantid'] = $this->Merchant_id;
        $params['transactionid'] = $Transaction_id;
        $params['deliverydate'] = $Delivery_date;
        $params['mac'] = md5(implode('', $params).$this->Secret_word);
        $result = $this->_soapClient->confirm($params);
        return (array) $result->return;
    }

    /**
     * query for the status of a transations using the Customer_refno id
     *
     * @param TODO $Customer_refno the shops identifyer for the transation
     *
     * @return \ref AdminResponse array
     */
    function query($Customer_refno)
    {
        $params['merchantid'] = $this->Merchant_id;
        $params['customerrefno'] = $Customer_refno;
        $params['mac'] = md5(implode('', $params).$this->Secret_word);
        $result = $this->_soapClient->query($params);
        return (array) $result->return;
    }
}

