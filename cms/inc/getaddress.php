<?php
function getAddress($id) {
	
	
	$default['recName1'] = '';
	$default['recAddress1'] = '';
	$default['recZipCode'] = '';
	$default['recCVR'] = '';
	$default['recAttPerson'] = '';
	$default['recAddress2'] = '';
	$default['recPostBox'] = '';
	$default['email'] = '';
	
	$dbs[0]['mysql_server'] = 'jagtogfiskerimagasinet.dk.mysql';
	$dbs[0]['mysql_user'] = 'jagtogfiskerima';
	$dbs[0]['mysql_password'] = 'GxYqj5EX';
	$dbs[0]['mysql_database'] = 'jagtogfiskerima';
	$dbs[1]['mysql_server'] = 'huntershouse.dk.mysql';
	$dbs[1]['mysql_user'] = 'huntershouse_dk';
	$dbs[1]['mysql_password'] = 'sabbBFab';
	$dbs[1]['mysql_database'] = 'huntershouse_dk';
	$dbs[2]['mysql_server'] = 'arms-gallery.dk.mysql';
	$dbs[2]['mysql_user'] = 'arms_gallery_dk';
	$dbs[2]['mysql_password'] = 'hSKe3eDZ';
	$dbs[2]['mysql_database'] = 'arms_gallery_dk';
	$dbs[3]['mysql_server'] = 'geoffanderson.com.mysql';
	$dbs[3]['mysql_user'] = 'geoffanderson_c';
	$dbs[3]['mysql_password'] = '2iEEXLMM';
	$dbs[3]['mysql_database'] = 'geoffanderson_c';
	
	require_once $_SERVER['DOCUMENT_ROOT'].'/inc/mysqli.php';
	
	foreach($dbs as $db) {
		$mysqli_ext = new simple_mysqli($db['mysql_server'], $db['mysql_user'], $db['mysql_password'], $db['mysql_database']);
		
		//try packages
		if($user = $mysqli_ext->fetch_array('SELECT recName1, recAddress1, recZipCode FROM `post` WHERE `recipientID` LIKE \''.$id.'\' ORDER BY id DESC LIMIT 1')) {
			$return = array_merge($default, $user[0]);
			if($return != $default)
				return $return;
		}
		
		//Try katalog orders
		if($user = $mysqli_ext->fetch_array('SELECT navn, email, adresse, post FROM `email` WHERE `tlf1` LIKE \''.$id.'\' OR `tlf2` LIKE \''.$id.'\' ORDER BY id DESC LIMIT 1')) {
			$return['recName1'] = $user[0]['navn'];
			$return['recAddress1'] = $user[0]['adresse'];
			$return['recZipCode'] = $user[0]['post'];
			$return['email'] = $user[0]['email'];
			$return = array_merge($default, $return);
			
			if($return != $default)
				return $return;
		}
		
		//Try fakturas
		if($user = $mysqli_ext->fetch_array('SELECT navn, email, att, adresse, postnr, postbox FROM `fakturas` WHERE `tlf1` LIKE \''.$id.'\' OR `tlf2` LIKE \''.$id.'\' ORDER BY id DESC LIMIT 1')) {
		$return['recName1'] = $user[0]['navn'];
		$return['recAddress1'] = $user[0]['adresse'];
		$return['recZipCode'] = $user[0]['postnr'];
		$return['recAttPerson'] = $user[0]['att'];
		$return['recPostBox'] = $user[0]['postbox'];
		$return['email'] = $user[0]['email'];
		$return = array_merge($default, $return);
			
		if($return != $default)
			return $return;
		}
	}
		
	require_once($_SERVER['DOCUMENT_ROOT'].'/krak/krak.php');
	if($return = getAddressKrak($id)) {
		$return = array_merge($default, $return);
			
		if($return != $default)
			return $return;
	} else {
	//Addressen kunde ikke findes.
		return array('error' => _('The address could not be found.'));
	}
}
?>