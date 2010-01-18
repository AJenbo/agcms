<?php
//Site settings
$GLOBALS['_config']['base_url'] = 'http://huntershouse.dk';
$GLOBALS['_config']['site_name'] = 'Hunters House A/S';
$GLOBALS['_config']['address'] = 'H.C. Ørsteds Vej 7 B';
$GLOBALS['_config']['postcode'] = '1879';
$GLOBALS['_config']['city'] = 'Frederiksberg C';
$GLOBALS['_config']['phone'] = '33 222 333';
$GLOBALS['_config']['fax'] = '33 14 04 07';

//Email settings
$GLOBALS['_config']['email'][] = 'mail@huntershouse.dk';
$GLOBALS['_config']['emailpasswords'][] = '.357magnum';
$GLOBALS['_config']['email'][] = 'fisk@huntershouse.dk';
$GLOBALS['_config']['emailpasswords'][] = 'risris';
$GLOBALS['_config']['email'][] = 'karpegrej@huntershouse.dk';
$GLOBALS['_config']['emailpasswords'][] = 'buzzwar';

//IMAP settings
$GLOBALS['_config']['imap'] = 'imap.huntershouse.dk';
$GLOBALS['_config']['imapport'] = '143';
$GLOBALS['_config']['emailsent'] = 'INBOX.Sent';

//SMTP settings
$GLOBALS['_config']['smtp'] = 'mailout.one.com';
$GLOBALS['_config']['smtpport'] = 25;
$GLOBALS['_config']['emailpassword'] = false;

$GLOBALS['_config']['interests'][] = 'Fiskeri';
$GLOBALS['_config']['interests'][] = 'Jagt';
$GLOBALS['_config']['interests'][] = 'Tøj';

//PBS settings
$GLOBALS['_config']['pbsid'] = '3025';
$GLOBALS['_config']['pbspassword'] = 'afn5gy9hxb62zv4unnce4ghbdykwcp2x';
$GLOBALS['_config']['pbsfix'] = 'HH';

//MySQL settings
$GLOBALS['_config']['mysql_server'] = 'huntershouse.dk.mysql';
$GLOBALS['_config']['mysql_user'] = 'huntershouse_dk';
$GLOBALS['_config']['mysql_password'] = 'sabbBFab';
$GLOBALS['_config']['mysql_database'] = 'huntershouse_dk';
?>
