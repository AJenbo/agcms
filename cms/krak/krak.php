<?php
function objectToArray($object) {
	$object = (array) $object;
	foreach($object as $key => $value ){
		if(is_object($value) || is_array($value))
			$object[$key] = objectToArray($value);
		}
	return $object;
}

function getAddressKrak($telephoneNumber) {
	
	$username = 'jagtogfiskerimagasinet_dk'; 	// Set username here
	$password = '.357magnum'; 					// Set password here
	$locale = 'da-DK';
	$product = 'IIP-TFORM-OPH1';				// Substitute with the correct product number
	
	$SoapClient = new SoapClient("http://login.webservice.krak.dk/ticketcentral.asmx?op=GetTicketByUser&wsdl");
	$params['userName'] = $username;
	$params['password'] = $password;
	$params['locale'] = $locale;
	$result = $SoapClient->GetTicketByUser($params);
	$SoapClient = new SoapClient("http://basicservices.webservice.krak.dk/telesearch.asmx?op=GetTeleByTn&wsdl");
	$headers[] = new SoapHeader('http://webservice.krak.dk/', 'ticket', $result->GetTicketByUserResult->ticket);
	$headers[] = new SoapHeader('http://webservice.krak.dk/', 'product', $product);
	$headers[] = new SoapHeader('http://webservice.krak.dk/', 'username', $username);
	$SoapClient->__setSoapHeaders($headers);
	$params['telephoneNumber'] = $telephoneNumber;
	$result = objectToArray($SoapClient->GetTeleByTn($params));
	
	//Convert to oure format

	if(!$result['GetTeleByTnResult'])
		return false;
	
	$return['recName1'] = '';
	if(!empty($result['GetTeleByTnResult']['Tele']['CompanyName'])) {
		$return['recName1'] = $result['GetTeleByTnResult']['Tele']['CompanyName'];
	}
	if(!$return['recName1']) {
		if(!empty($result['GetTeleByTnResult']['Tele']['FirstName'])) {
			$return['recName1'] = $result['GetTeleByTnResult']['Tele']['FirstName'];
		}
		if(!empty($result['GetTeleByTnResult']['Tele']['LastName'])) {
			$return['recName1'] .= ' '.$result['GetTeleByTnResult']['Tele']['LastName'];
		}
	}
	$return['recName1'] = trim($return['recName1']);
	
	$return['recAddress1'] = '';
	if(!empty($result['GetTeleByTnResult']['Tele']['Address']['PlaceName']))
		$return['recAddress1'] .= $result['GetTeleByTnResult']['Tele']['Address']['PlaceName'];
	if(!empty($result['GetTeleByTnResult']['Tele']['Address']['RoadName']))
		$return['recAddress1'] .= ' '.$result['GetTeleByTnResult']['Tele']['Address']['RoadName'];
	if(!empty($result['GetTeleByTnResult']['Tele']['Address']['HouseNumberNumericFrom']))
		$return['recAddress1'] .= ' '.$result['GetTeleByTnResult']['Tele']['Address']['HouseNumberNumericFrom'];
	if(!empty($result['GetTeleByTnResult']['Tele']['Address']['HouseNumberCharacterFrom']))
		$return['recAddress1'] .= $result['GetTeleByTnResult']['Tele']['Address']['HouseNumberCharacterFrom'];
	if(!empty($result['GetTeleByTnResult']['Tele']['Address']['Floor']))
		$return['recAddress1'] .= ' '.$result['GetTeleByTnResult']['Tele']['Address']['Floor'];
	if(!empty($result['GetTeleByTnResult']['Tele']['Address']['Door']))
		$return['recAddress1'] .= ' '.$result['GetTeleByTnResult']['Tele']['Address']['Door'];
	$return['recAddress1'] = trim($return['recAddress1']);

	
	$return['recZipCode'] = trim($result['GetTeleByTnResult']['Tele']['Address']['PostalCode']);
	$return['recCVR'] = '';
	$return['recAttPerson'] = '';
	$return['recAddress2'] = '';
	$return['recPostBox'] = '';
	$return['recAttPerson'] = '';
	$return['recAddress2'] = '';
	$return['recPostBox'] = '';
	$result['GetTeleByTnResult']['Tele']['ContactInfo']['EmailAddress']
	if(!empty($result['GetTeleByTnResult']['Tele']['ContactInfo']['EmailAddress'])) {
		$return['email'] = trim($result['GetTeleByTnResult']['Tele']['ContactInfo']['EmailAddress']);
	}
		
	if($return['recName1'] || $return['recAddress1'] || $return['recZipCode'])
		return $return;
	else
		return false;
}
?>