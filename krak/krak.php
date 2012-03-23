<?php
/**
 * Interact with krak web service
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

/**
 * Get address from phone number
 *
 * @param string $phoneNumber Phone number
 *
 * @return array Array with address fitting the post table format
 */
function getAddressKrak($phoneNumber)
{
    $username = 'jagtogfiskerimagasinet_dk'; 	// Set username here
    $password = '.357magnum'; 	 // Set password here
    $locale = 'da-DK';
    $product = 'IIP-TFORM-OPH1';	 // Substitute with the correct product number

    $SoapClient = new SoapClient(
        "http://login.webservice.krak.dk/ticketcentral.asmx?op=GetTicketByUser&wsdl"
    );
    $params['userName'] = $username;
    $params['password'] = $password;
    $params['locale'] = $locale;
    $result = $SoapClient->GetTicketByUser($params);
    $SoapClient = new SoapClient(
        "http://basicservices.webservice.krak.dk/telesearch.asmx?op=GetTeleByTn&wsdl"
    );
    $headers[] = new SoapHeader(
        'http://webservice.krak.dk/',
        'ticket',
        $result->GetTicketByUserResult->ticket
    );
    $headers[] = new SoapHeader(
        'http://webservice.krak.dk/',
        'product',
        $product
    );
    $headers[] = new SoapHeader('http://webservice.krak.dk/', 'username', $username);
    $SoapClient->__setSoapHeaders($headers);
    $params['telephoneNumber'] = $phoneNumber;
    $result = $SoapClient->GetTeleByTn($params);

    //Convert to oure format

    if (!$result->GetTeleByTnResult) {
        return false;
    }

    $result = $result->GetTeleByTnResult->Tele;

    $return['recName1'] = '';
    if (!empty($result->CompanyName)) {
        $return['recName1'] = (string) $result->CompanyName;
    }
    if (!$return['recName1']) {
        if (!empty($result->FirstName)) {
            $return['recName1'] = (string) $result->FirstName;
        }
        if (!empty($result->LastName)) {
            $return['recName1'] .= ' ' . (string) $result->LastName;
        }
    }
    $return['recName1'] = trim((string) $return->recName1);

    $address = '';
    if (!empty($result->Address->PlaceName)) {
        $address .= (string) $result->Address->PlaceName;
    }
    if (!empty($result->Address->RoadName)) {
        $address .= ' ' . (string) $result->Address->RoadName;
    }
    if (!empty($result->Address->HouseNumberNumericFrom)) {
        $address .= ' ' . (string) $result->Address->HouseNumberNumericFrom;
    }
    if (!empty($result->Address->HouseNumberCharacterFrom)) {
        $address .= (string) $result->Address->HouseNumberCharacterFrom;
    }
    if (!empty($result->Address->Floor)) {
        $address .= ' ' . (string) $result->Address->Floor;
    }

    if (!empty($result->Address->Door)) {
        $address .= ' ' . (string) $result->Address->Door;
    }
    $return['recAddress1'] = trim($address);


    $return['recZipCode'] = trim((string) $result->Address->PostalCode);
    $return['recCVR'] = '';
    $return['recAttPerson'] = '';
    $return['recAddress2'] = '';
    $return['recPostBox'] = '';
    $return['recAttPerson'] = '';
    $return['recAddress2'] = '';
    $return['recPostBox'] = '';
    if (!empty($result->ContactInfo->EmailAddress)) {
        $return['email'] = trim((string) $result->ContactInfo->EmailAddress);
    }

    if ($return['recName1'] || $return['recAddress1'] || $return['recZipCode']) {
        return $return;
    } else {
        return false;
    }
}

