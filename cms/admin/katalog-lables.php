<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css" media="print">
form, .web {
	display:none;
}
</style>
<style type="text/css">
@page {
 margin:0;
}
* {
	font-family:Geneva, Arial, Helvetica, sans-serif;
	font-size:13pt;
	margin:0;
	padding:0;
	border-collapse:collapse;
	border-spacing:0;
}
h1 {
	text-align:center;
}
div.table {
	width:19.6cm;
	margin:1.12cm 0 0 0.4cm; /**/
}
div.tr {
	height:3.80cm;
	page-break-inside:avoid;
	page-break-before:avoid;
	page-break-after:avoid;
	clear:both;
}
div.td {
	text-align:center;
	vertical-align:middle;
	width:6.30cm;
	margin:0 0.34cm;
	float:left;
	height:3.80cm;
	display: table-cell;
	vertical-align: middle
}
div.left {
	margin:0cm;
}
div.left, div.right {
	margin:0cm;
}
table {
	height:100%;
	width:100%;
}
td {
	vertical-align:middle;
}
p.line8 {
	page-break-before:always;
	line-height:0.1mm;
}
</style>
<title>Katalog lables</title>
</head>
<body><?php

if($_GET['dato'])
	$dato = $_GET['dato'];
else
	$dato = date('Y-m-d', time()-7*24*60*60);

?><form action="" method="get" style="text-align:center">
  <input type="submit" value="Vis nyere end" accesskey="f" />
  <input value="<?php echo($dato); ?>" name="dato" />
</form><?php

if($_GET['dato']) {
	require_once '../inc/config.php';
	require_once '../inc/mysqli.php';
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	$email = $mysqli->fetch_array("SELECT `navn` , `adresse` , `land` , `post` , `by` FROM `email` 
	WHERE `dato` > '".$_GET['dato']." 00:00:00'
	AND `navn` != '' AND `adresse` != '' AND `post` != '' AND `by` != '' AND `downloaded` = '0' ORDER BY dato");
	
	if($email) {
		//Pad rows to fit on 
		$email_nr = ceil(count($email)/21)*21;
		echo('<h1 class="web">'.($email_nr/21).' sider</h1>');
		
		for($i=0;$i<$email_nr;$i++) {
			
			//Bigin new table
			if(!$i % 21 && !$i) {
				?><div class="table"><?php
			} elseif($i % 21 == 0) {
				?><p class="line8">&nbsp;</p><div class="table"><?php
			} 
			
			//Print cells (and row)
			if($i % 3 == 0) {
				?><div class="tr"><div class="td left"><?php
			} elseif($i % 3 == 1) {
				?><div class="td"><?php
			} elseif($i % 3 == 2) {
				?><div class="td right"><?php
			}
			echo('<table><tr><td>'.$email[$i]['navn'].'<br />'
			.$email[$i]['adresse'].'<br />'
			.$email[$i]['post'].' '.$email[$i]['by']);
			if($email[$i]['land'] != 'Danmark') echo ('<br />'.$email[$i]['land']);
			 ?></td></tr></table></div><?
			//end row
			if($i % 3 == 2) {
				?></div><?php
			}
			
			//end table
			if($i % 21 == 20) {
				?></div><?php
			}
		}
	}
}
?></body>
