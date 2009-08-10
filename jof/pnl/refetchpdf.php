<?php
mb_language("uni");
mb_internal_encoding('UTF-8');

require_once 'snoopy/snoopy.class.php';
require_once 'config.php';
$snoopy = new Snoopy;

//Logon start
$submit_url = 'http://online.pannordic.com/pn_logistics/index.jsp';

$submit_vars = array();
$submit_vars['username'] = $GLOBALS['_config']['username'];
$submit_vars['j_username'] = $GLOBALS['_config']['username'];
$submit_vars['password'] = $GLOBALS['_config']['password'];
$submit_vars['sv'] = '';
$submit_vars['j_password'] = $GLOBALS['_config']['password'];
$submit_vars['login_button'] = 'Login';

$submit_vars = array_map('utf8_decode', $submit_vars);
$snoopy->submit($submit_url, $submit_vars);
$snoopy->setcookies();

$submit_url = 'http://online.pannordic.com/pn_logistics/j_security_check';

$submit_vars = array();
$submit_vars['j_username'] = $GLOBALS['_config']['username'];
$submit_vars['j_password'] = strtoupper($GLOBALS['_config']['password']);

$submit_vars = array_map('utf8_decode', $submit_vars);
$snoopy->submit($submit_url, $submit_vars);
//Logon end

//Ready pdf
$submit_url = 'http://online.pannordic.com/pn_logistics/print_label_query.jsp?printerType=LASER2&bookingDate='.$_GET['bookingDate'].'&bookingTime='.$_GET['bookingTime'].'&bookingUserId='.$GLOBALS['_config']['username'].'&actionBookingsOverview=LASER2';
$snoopy->fetch($submit_url);

//Featch pdf
$submit_url = 'http://online.pannordic.com/pn_logistics/PrintPdfServ?multipleLabel=Y&printerType=LASER2&labelType='.$_GET['labelType'];
$snoopy->fetch($submit_url);

//Forwared header
foreach($snoopy->headers as $header) {
	header($header);
}

//Out put pdf
echo($snoopy->results);
?>