<?php
function getAddress($id) {
	require_once 'mysqli.php';
	require_once 'config.php';
//	$mysqli_jof = new simple_mysqli('localhost', 'jagtogfiskerimag', '.460jagtogfiskeri', 'jagtogfiskerimaga_dk001');
	$mysqli_jof = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);


	//try packages
	if($user = $mysqli_jof->fetch_array('SELECT recName1, recAddress1, recZipCode FROM `post` WHERE `recipientID` LIKE \''.$id.'\' ORDER BY id DESC LIMIT 1')) {
		$return = $user[0];
	//Try katalog orders
	} elseif($user = $mysqli_jof->fetch_array('SELECT navn, email, adresse, post FROM `email` WHERE `tlf1` LIKE \''.$id.'\' OR `tlf2` LIKE \''.$id.'\' ORDER BY id DESC LIMIT 1')) {
		$return['recName1'] = $user[0]['navn'];
		$return['recAddress1'] = $user[0]['adresse'];
		$return['recZipCode'] = $user[0]['post'];
		$return['email'] = $user[0]['email'];
	//Try fakturas
	} elseif($user = $mysqli_jof->fetch_array('SELECT navn, email, att, adresse, postnr FROM `fakturas` WHERE `tlf1` LIKE \''.$id.'\' OR `tlf2` LIKE \''.$id.'\' ORDER BY id DESC LIMIT 1')) {
		$return['recName1'] = $user[0]['navn'];
		$return['recAddress1'] = $user[0]['adresse'];
		$return['recZipCode'] = $user[0]['postnr'];
		$return['recAttPerson'] = $user[0]['att'];
		$return['recPostBox'] = $user[0]['postbox'];
		$return['email'] = $user[0]['email'];
	//try HH
	} else {
		/*
		$mysqli_hh = new simple_mysqli($GLOBALS['_config']['mysql_server'], 'huntershouse2_dk', '.460weatherby2', 'huntershouse2_dk001');
		if($user = $mysqli_hh->fetch_array('SELECT navn, email, adresse, post FROM `email` WHERE `tlf1` LIKE \''.$id.'\' OR `tlf2` LIKE \''.$id.'\' ORDER BY id DESC LIMIT 1')) {
			$return['recName1'] = $user[0]['navn'];
			$return['recAddress1'] = $user[0]['adresse'];
			$return['recZipCode'] = $user[0]['post'];
			$return['email'] = $user[0]['email'];
		//Try fakturas
		} elseif($user = $mysqli_hh->fetch_array('SELECT navn, email, att, adresse, postnr FROM `fakturas` WHERE `tlf1` LIKE \''.$id.'\' OR `tlf2` LIKE \''.$id.'\' ORDER BY id DESC LIMIT 1')) {
			$return['recName1'] = $user[0]['navn'];
			$return['recAddress1'] = $user[0]['adresse'];
			$return['recZipCode'] = $user[0]['postnr'];
			$return['recAttPerson'] = $user[0]['att'];
			$return['recPostBox'] = $user[0]['postbox'];
			$return['email'] = $user[0]['email'];
		//try krak
		} else {
		*/
			require_once('../krak/krak.php');
			$return = getAddressKrak($id);
		//}
	}
	
	if(empty($return['recName1']))
		$return['recName1'] = '';
	if(empty($return['recAddress1']))
		$return['recAddress1'] = '';
	if(empty($return['recZipCode']))
		$return['recZipCode'] = '';
	if(empty($return['recCVR']))
		$return['recCVR'] = '';
	if(empty($return['recAttPerson']))
		$return['recAttPerson'] = '';
	if(empty($return['recAddress2']))
		$return['recAddress2'] = '';
	if(empty($return['recPostBox']))
		$return['recPostBox'] = '';
	if(empty($return['email']))
		$return['email'] = '';
		
	if($return['recName1'] || $return['recAddress1'] || $return['recZipCode'] || $return['recCVR'] || $return['recAttPerson'] || $return['recAddress2'] || $return['recPostBox'] || $return['email'])
		return $return;
	else
		//None found return error
		return array('error' => 'Addressen kunde ikke findes.');
}
?>