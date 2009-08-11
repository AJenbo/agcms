<?php
//Validate md5 and id to set to true
function getCheckid($id) {
	return mb_substr(md5($id.'salt24raej098'), 3, 5);
}

if(!$_GET['step'])
	$_GET['step'] = 1;
if(getCheckid($_GET['id']) != $_GET['checkid'])
	$_GET['step'] = 0;


//Scannet's betalings modul køre i latin 1 (iso 8859-1) så vi skal skifte til iso-8859-1 som er tekst mæsig ens.
if($_GET['step'] == 3 || $_GET['step'] == 4 || $_GET['step'] == '4b') {
	header('Content-type: text/html; charset=iso-8859-1');
	$GLOBALS['generatedcontent']['encoding'] = 'iso-8859-1';
	function page_utf8_decode($buffer) {
		$buffer = utf8_decode($buffer);
		$buffer = str_replace('/theme/style.css' ,'theme/style.css' , $buffer);
		$buffer = str_replace('/theme/print.css' ,'theme/print.css' , $buffer);
		$buffer = str_replace('/theme/handheld.css' ,'theme/handheld.css' , $buffer);
		$buffer = str_replace('/javascript/javascript.js' ,'javascript/javascript.js' , $buffer);
		$buffer = str_replace('/images/web/logo.gif' ,'images/web/logo.gif' , $buffer);
		$buffer = str_replace('/theme/javascript.js' ,'theme/javascript.js' , $buffer);
		$buffer = str_replace('/theme/ie6fix.js' ,'theme/ie6fix.js' , $buffer);
		$buffer = str_replace('/sog.php' ,'sog.php' , $buffer);
		$buffer = str_replace('/theme/ieupdate.js' ,'theme/ieupdate.js' , $buffer);
		$buffer = str_replace('"/' ,'"http://huntershouse.dk/' , $buffer);
		return $buffer;
	}
	ob_start('page_utf8_decode');
}

$delayprint = true;
chdir('../');
include('index.php');

include('faktura/inc/step'.$_GET['step'].'.php');
$GLOBALS['generatedcontent']['contenttype'] = 'page';
require_once 'theme/index.php';

if($_GET['step'] == 3 || $_GET['step'] == 4 || $_GET['step'] == '4b')
	ob_end_flush();
?>