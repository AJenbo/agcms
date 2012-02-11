<?php
require_once '../inc/sajax.php';
require_once "../inc/config.php";
function get_mailbox_size($email, $mailbox) {
	require_once "../inc/imap.inc.php";
	$size = 0;
	$imap = new IMAPMAIL;
	$imap->open($GLOBALS['_config']['imap'], $GLOBALS['_config']['imapport']);
	$imap->login($GLOBALS['_config']['email'][$email], $GLOBALS['_config']['emailpasswords'][$email]);
	
	$mailSizes = array();
	$mailboxStatus = $imap->open_mailbox($mailbox, true);
	preg_match_all('/SIZE\s([0-9]+)/', $imap->fetch_mail('1:*', 'RFC822.SIZE'), $mailSizes);
	if ($mailSizes)
		$size += array_sum($mailSizes[1]);
	return $size;
}

$sajax_debug_mode = 0;
sajax_export(array('name' => 'get_mailbox_size', 'method' => 'GET', "asynchronous" => false));
sajax_handle_client_request();
?>