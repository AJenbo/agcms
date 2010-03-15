<?php

require_once("inc/config.php");

//Colect interests
$interests = '';
foreach($GLOBALS['_config']['interests'] as $interest) {
	if(@$_POST[preg_replace('/\\s/u', '_', $interest)]) {
		if($interests)
			$interests .= '<';
		$interests .= $interest;
	}
}

$downloaded = 1-@$_POST['nodownload'];

//Does the host have a valid 
function valide_mail_host($host) {
	return getmxrr(preg_replace('/.+?@(.?)/u', '$1', $host), $dummy);
}

//is the email valid
if(!preg_match('/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i', $_POST['email']) || !valide_mail_host($_POST['email'])) {
	$_POST['email'] = '';
	$email_rejected = true;
}
	
if(($_POST['adresse'] && ($_POST['post'] || $_POST['by'])) || !$email_rejected || $_POST['tlf1'] || $_POST['tlf2']) {
	//Save to database
	require_once("inc/mysqli.php");
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	
	$mysqli->query("INSERT INTO `email` (`navn`, `email`, `adresse`, `land`, `post`, `by`, `tlf1`, `tlf2`, `kartotek`, `interests`, `dato` , `downloaded` , `ip` )
	VALUES ('".$_POST['navn']."', '".$_POST['email']."', '".$_POST['adresse']."', '".$_POST['land']."', '".$_POST['post']."', '".$_POST['by']."', '".$_POST['tlf1']."', '".$_POST['tlf2']."', '".$_POST['tilfoj']."', '".@$interests."', now(), '".$downloaded."', '".$_SERVER['REMOTE_ADDR']."')");
	
	$mysqli->close();
}

//Generate return page
$GLOBALS['generatedcontent']['activmenu'] = 545;

$delayprint = true;

require_once 'index.php';


$GLOBALS['generatedcontent']['contenttype'] = 'page';	
$GLOBALS['generatedcontent']['title'] = 'Tak for tilmeldingen';
$GLOBALS['generatedcontent']['headline'] = 'Tak for tilmeldingen';
$GLOBALS['generatedcontent']['text'] = '<p>Vi har med tak modtaget deres tilmeldingen.</p>';

if($email_rejected)
	$GLOBALS['generatedcontent']['text'] .= '<p>Deres email adresse blev ikke godkendt!</p>';

$GLOBALS['generatedcontent']['text'] .= '<p>Med vendlig hilsen<br />'.$GLOBALS['_config']['site_name'].'</p>';

$GLOBALS['generatedcontent']['keywords'] = NULL;
$GLOBALS['generatedcontent']['list'] = NULL;

//Print page
require_once 'theme/index.php';
?>
