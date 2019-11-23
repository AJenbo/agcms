<?php namespace App\Services;

use App\Models\Epayment;
use SoapClient;
use stdClass;

/**
 * A helper class for communication with ePay.
 *
 * See http://www.betalingsterminal.no/Netthandel-forside/Teknisk-veiledning/API/ for
 * a description of the returned objects
 */
class EpaymentService
{
    /** @var string Shops merchant id. */
    private $merchantId;

    /** @var string Service password. */
    private $password;

    /** @var ?SoapClient Service connection. */
    private $soapClient;

    /** @var string[] */
    const PAYMENT_TYPES = [
        1  => 'Dankort/Visa-Dankort',
        3  => 'Visa / Visa Electron',
        4  => 'MasterCard',
        6  => 'JCB',
        7  => 'Maestro',
        8  => 'Diners Club',
        9  => 'American Express',
        11 => 'Forbrugsforeningen',
        12 => 'Nordea e-betaling',
        13 => 'Danske Netbetalinger',
        14 => 'PayPal',
        17 => 'Klarna',
        19 => 'SEB (SE)',
        20 => 'Nordea (SE)',
        21 => 'Handelsbanken (SE)',
        22 => 'Swedbank (SE)',
        23 => 'ViaBill',
        24 => 'Beeptify',
        25 => 'iDEAL',
        27 => 'Paii',
        28 => 'Brandts Gavekort',
        29 => 'MobilePay Online',
    ];

    /**
     * Setup the class variables for initialization.
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
     * Get name of payment type.
     *
     * @param int $paymentType
     *
     * @return string
     */
    public static function getPaymentName(int $paymentType): string
    {
        return self::PAYMENT_TYPES[$paymentType];
    }

    /**
     * @param string $orderId The order id
     *
     * @return Epayment
     */
    public function getPayment(string $orderId): Epayment
    {
        $transactionData = $this->getTransactionData($orderId);

        return new Epayment($this, $transactionData);
    }

    /**
     * Setup the connection to the API.
     */
    private function getConnection(): SoapClient
    {
        if (!$this->soapClient) {
            $this->soapClient = new SoapClient(
                'https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL',
                [
                    'soap_version' => SOAP_1_2,
                    'features'     => SOAP_SINGLE_ELEMENT_ARRAYS,
                    'trace'        => true,
                    'exceptions'   => true,
                ]
            );
        }

        return $this->soapClient;
    }

    /**
     * Fetch the transation data.
     *
     * @param string $orderId Shop order id
     *
     * @return stdClass
     */
    private function getTransactionData(string $orderId): stdClass
    {
        foreach (['PAYMENT_CAPTURED', 'PAYMENT_NEW', 'PAYMENT_DELETED'] as $status) {
            $response = $this->getConnection()->gettransactionlist($this->getSearchData($orderId, $status));
            if (!empty($response->transactionInformationAry->TransactionInformationType)) {
                return first($response->transactionInformationAry->TransactionInformationType);
            }
        }

        $transactionData = new stdClass();
        $transactionData->status = '';
        $transactionData->transactionid = 0;
        $transactionData->authamount = 0;

        return $transactionData;
    }

    /**
     * Generate a search request.
     *
     * @param string $orderId
     * @param string $status
     *
     * @return array
     */
    private function getSearchData(string $orderId, string $status): array
    {
        return [
            'pwd'             => $this->password,
            'merchantnumber'  => $this->merchantId,
            'searchorderid'   => $orderId,
            'status'          => $status,
            'searchdatestart' => '2014-06-19T00:00:00+02:00',
            'searchdateend'   => date('c'),
            'epayresponse'    => true,
        ];
    }

    /**
     * Canncels a payment transation.
     *
     * @param Epayment $epayment
     *
     * @return bool
     */
    public function annul(Epayment $epayment): bool
    {
        $response = $this->getConnection()->delete(
            [
                'pwd'            => $this->password,
                'merchantnumber' => $this->merchantId,
                'transactionid'  => $epayment->getId(),
                'epayresponse'   => true,
            ]
        );

        if ($response->deleteResult) {
            return true;
        }

        return false;
    }

    /**
     * Confirm the transation and draw the amount from the users account.
     *
     * @param Epayment $epayment
     * @param int      $amount   The amount to draw from the customers account
     *
     * @return bool
     */
    public function confirm(Epayment $epayment, int $amount): bool
    {
        $response = $this->getConnection()->capture([
            'pwd'            => $this->password,
            'merchantnumber' => $this->merchantId,
            'transactionid'  => $epayment->getId(),
            'amount'         => $amount,
            'epayresponse'   => true,
            'pbsResponse'    => true,
        ]);

        if ($response->captureResult) {
            return true;
        }

        return false;
    }
}
