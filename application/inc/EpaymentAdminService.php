<?php namespace AGCMS;

use SoapClient;
use stdClass;

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
     * Setup the class variables for initialization
     *
     * @param string $merchantId Id provided by PBS identifying the shop
     * @param string $password   Password for service
     */
    public function __construct(string $merchantId, string $password)
    {
        $this->merchantId = $merchantId;
        $this->password = $password;
    }

    /**
     * @param string $orderId The order id
     *
     * @return Epayment
     */
    public function getPayment(string $orderId): Epayment
    {
        $this->openConnection();
        $transactionData = $this->getTransactionData($orderId);

        return new Epayment($this, $transactionData);
    }

    /**
     * @return void
     */
    private function openConnection()
    {
        if ($this->soapClient) {
            return;
        }

        $this->soapClient = new SoapClient(
            'https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL'
        );
    }

    /**
     * Fetch the transation data
     *
     * @param string $orderId Shop order id
     *
     * @return stdClass
     */
    private function getTransactionData(string $orderId): stdClass
    {
        foreach (['PAYMENT_NEW', 'PAYMENT_CAPTURED', 'PAYMENT_DELETED'] as $status) {
            $response = $this->soapClient->gettransactionlist($this->getSearchData($orderId, $status));
            if (!empty($response->transactionInformationAry) && (array) $response->transactionInformationAry) {
                return $response->transactionInformationAry->TransactionInformationType;
            }
        }

        $transactionData = new stdClass();
        $transactionData->status = '';
        $transactionData->transactionid = 0;
        $transactionData->authamount = 0;

        return $transactionData;
    }

    private function getSearchData(string $orderId, string $status): array
    {
        return [
            'pwd' => $this->password,
            'merchantnumber' => $this->merchantId,
            'searchorderid' => $orderId,
            'status' => $status,
            'searchdatestart' => '2014-06-19T00:00:00+02:00',
            'searchdateend' => date('c'),
            'epayresponse' => true,
        ];
    }

    /**
     * Canncels a payment transation
     *
     * @param Epayment $epayment
     *
     * @return bool
     */
    public function annul(Epayment $epayment): bool
    {
        $response = $this->soapClient->delete(
            [
                'pwd' => $this->password,
                'merchantnumber' => $this->merchantId,
                'transactionid' => $epayment->getId(),
                'epayresponse' => true,
            ]
        );

        if ($response->deleteResult) {
            return true;
        }

        return false;
    }

    /**
     * Confirm the transation and draw the amount from the users account
     *
     * @param Epayment $epayment
     * @param int $amount The amount to draw from the customers account
     *
     * @return bool
     */
    public function confirm(Epayment $epayment, int $amount): bool
    {
        $response = $this->soapClient->capture([
            'pwd' => $this->password,
            'merchantnumber' => $this->merchantId,
            'transactionid' => $epayment->getId(),
            'amount' => $amount,
            'epayresponse' => true,
            'pbsResponse' => true,
        ]);

        if ($response->captureResult) {
            return true;
        }

        return false;
    }
}
