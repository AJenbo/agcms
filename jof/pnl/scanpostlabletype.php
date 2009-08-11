<?php
require_once 'snoopy-postu/snoopy.class.php';
$snoopy = new Snoopy;

//Login
$submit_vars = array();
$submit_vars['gotoURL'] = "/pfs/PfsLoginServlet";
$submit_vars['clientID'] = '150007792';
$submit_vars['userID'] = "admin";
$submit_vars['password'] = 'Chips18';
$submit_url = "http://www.postdanmark.dk/pfs/PfsLoginServlet";
//$submit_vars = array_map('utf8_decode', $submit_vars);
$snoopy->submit($submit_url, $submit_vars);

preg_match('/[0-9a-f]{240}/i', $snoopy->results, $token);

//TODO switch user

//Start lable form
$submit_vars = array();
$submit_vars['token'] = $token[0];
$submit_vars['programID'] = 'pfs';
$submit_vars['clientID'] = '150007792';
$submit_vars['userID'] = 'admin';
$submit_vars['sessionID'] = '0';
$submit_vars['accessCode'] = 'UC';
$submit_vars['exTime'] = '120';
$submit_vars['command'] = 'DETECT_X_FACILITIES';
$submit_vars['cpID'] = 'pfsWelcome.jsp';
$submit_vars['userAction'] = 'NEW';
$submit_url = 'http://www.postdanmark.dk/pfs/number80.do';
//$submit_vars = array_map('utf8_decode', $submit_vars);
$snoopy->submit($submit_url, $submit_vars);
$snoopy->setcookies();

preg_match('/[0-9a-f]{240}/i', $snoopy->results, $token);


$submit_vars = array();
$submit_vars['userAction'] = 'COUNTRY_SELECTED';
$submit_vars['formType'] = '';
$submit_vars['token'] = $token[0];
$submit_vars['programID'] = 'pfs';
$submit_vars['clientID'] = '150007792';
$submit_vars['userID'] = 'admin';
$submit_vars['sessionID'] = '0';
$submit_vars['accessCode'] = 'UC';
$submit_vars['exTime'] = '120';
$submit_vars['recipientUid'] = '0';
$submit_vars['returnAddressUid'] = '';
$submit_vars['recipient.interessentId'] = '';
$submit_vars['recipient.navn1'] = '';
$submit_vars['recipient.navn2'] = '';
$submit_vars['recipient.kontaktperson'] = '';
$submit_vars['recipient.adr1'] = '';
$submit_vars['recipient.adr2'] = '';
$submit_vars['recipient.postnr'] = '';
$submit_vars['recipient.bynavn'] = '';
$submit_vars['recipient.provinsStat'] = '';
$submit_vars['recipient.tlfnr'] = '';
$submit_vars['recipient.mobilTlfnr'] = '';
$submit_vars['recipient.email'] = '';
$submit_vars['productId'] = '';

require 'countries.php';

require 'countries_pdk.php';
foreach($pdkcountries as $country => $name) {
	//select country
	$submit_vars['recipient.landeid'] = $country;

	//$submit_vars = array_map('utf8_decode', $submit_vars);
	$snoopy->submit($submit_url, $submit_vars);

	preg_match('/<select\sname="productId".*?>(.*?)<\/select>/s', $snoopy->results, $options);

	preg_match_all('/<option\svalue="(.*?)">(.*?)<\/option>/s', $options[1], $options);

	echo('
'.$country);

	foreach($options[1] as $key => $option) {
		echo(' '.$option.' '.$options[2][$key]);
	}
}

?>