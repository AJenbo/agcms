<?php

$GLOBALS['generatedcontent']['crumbs'] = NULL;
$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => 'Faktura', 'link' => '/faktura/');
$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Dankort fejl', 'link' => '?error='.rawurlencode($_GET['error']).'&amp;ordrenr='.$_GET['id'].'&amp;tekst1='.$_GET['checkid']);

if(!$fakturas = $mysqli->fetch_array('SELECT * FROM `fakturas` WHERE id = '.$_GET['id'].' AND (status = \'new\' OR status = \'pbserror\' OR status = \'locked\') LIMIT 1')) {

	$GLOBALS['generatedcontent']['headline'] = 'Der er opstod følgende fejl';
	$GLOBALS['generatedcontent']['text'] = 'Ordren er muligvis allerede betalt.';
	
} else {

	$mysqli->query('UPDATE `fakturas` SET status = \'pbserror\' WHERE id = '.$_GET['id'].' AND status = \'new\' LIMIT 1');

	$GLOBALS['generatedcontent']['headline'] = 'Dankort fejl';
	$GLOBALS['generatedcontent']['text'] = '<h1>Der er opstod følgende fejl, ved betalingen:</h1><p>'.utf8_encode($_GET['error']).' <a href="#" onclick="history.back(); return false;">Prøv igen.</a></p>';
}
?>