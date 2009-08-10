<?php

	require_once("calcpakkepris".$_GET['y'].".php");

	require_once("clsWorkDays.php");

	function getTrackTrace($stregkode) {

		$snoopy = new Snoopy;

		$submit_url = "http://www.postdanmark.dk/tracktrace/TrackTrace.do?i_lang=IND&i_stregkode=".$stregkode;

		$snoopy->fetch($submit_url);

		preg_match('/<td>([.0-9]+)\skg<\\/td>/i', $snoopy->results, $kg);
		preg_match_all('/<td>([0-9]+)\smm.<\\/td>/i', $snoopy->results, $vol);

		$return[0] = ceil($kg[1]);
		$return[1] = $vol[1][0]/10;
		$return[2] = $vol[1][1]/10;
		$return[3] = $vol[1][2]/10;

		$return = array_map("html_entity_decode", $return);
		$return = array_map("trim", $return);

		return $return;
	}
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pakke priser</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
	body, table {
		font-size:9pt;
	}
	#date a {
		font-weight:bold;
		color:#000000;
	}
	td {
		border:1px #000000 solid;
		border-collapse:collapse;
		page-break-inside:avoid
	}
	tr, table {
		border-collapse:collapse;
		border:1px #000000 solid;
	}
</style>
<style type="text/css" media="screen">
	.print {
		display:none;
	}
	table {
		background-color:#dedede;
	}
	.altrow {
		background-color:#999999;
	}
</style>
</head>
<body>
<?php
require_once("../inc/mysqli.php");
require_once("../inc/config.php");

//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);


function rows($type) {
	global $mysqli;

	?><tr style="font-weight:bold;background-color:#999999;">
        <td>Dato</td>
        <td>Stregkode</td>
        <td>Faktureret</td>
        <td>Længde</td>
        <td>Bredde</td>
        <td>Højde</td>
        <td>Services</td>
        <td>Pris</td>
    </tr><?php
	
	if(!isset($_GET['m'])) {
		$_GET['m'] = date('n');
	}
	$post = $mysqli->fetch_array('SELECT *, UNIX_TIMESTAMP(`formDate`) as date FROM `post` WHERE deleted = 0 AND formDate >= \''.$_GET['y'].'-'.$_GET['m'].'-01\' AND formDate <= \''.$_GET['y'].'-'.$_GET['m'].'-31\' AND optRecipType = \''.$type.'\' ORDER BY `optRecipType` ASC, formDate, `STREGKODE` ASC');
	$post_nr = count($post);
	
	for($i=0;$i<$post_nr;$i++) {
		$fragt = pakkepris($post[$i]['pd_height'], $post[$i]['pd_width'], $post[$i]['pd_length'], $post[$i]['pd_weight'], $post[$i]['optRecipType'], $post[$i]['ss1'], $post[$i]['ss46'], $post[$i]['ss5amount'], false);
		$totalPorto += $fragt;
		?>
		<tr<?php if($i % 2) echo(' class="altrow"');	?>>
		<td style="text-align:left"><?php echo(date('dm', $post[$i]['date'])); ?></td>
		<td style="text-align:left"><?php echo($post[$i]['STREGKODE']); ?></td>
		<td style="text-align:right"><?php
		if($type != 'P') 
			$vkg = $post[$i]['pd_length']*$post[$i]['pd_width']*$post[$i]['pd_height']/4;
		echo(max($post[$i]['pd_weight']*1000, round($vkg))); ?></td>
		<td style="text-align:right"><?php echo($post[$i]['pd_length']*10); ?></td>
		<td style="text-align:right"><?php echo($post[$i]['pd_width']*10); ?></td>
		<td style="text-align:right"><?php echo($post[$i]['pd_height']*10); ?></td>
		<td><?php
		if($type == 'P' && calcvolume($post[$i]['pd_height'], $post[$i]['pd_width'], $post[$i]['pd_length']))
			echo('Vo');
		if($post[$i]['ss5amount'] == 'true')
			echo('Va');
		if($post[$i]['ss1'] == 'true')
			echo('Fo');
		if($post[$i]['ss46'] == 'true')
			echo('Lø');
		?></td>
		<td style="text-align:right"><?php echo(number_format($fragt, 2, ',', '.')); ?></td>
	</tr><?php
	}
	
	$GLOBALS['totalPorto'] += $totalPorto;
	
	?><tr style="font-weight:bold"><td colspan="8" style="text-align:right"><?php echo(number_format($totalPorto, 2, ',', '.')) ?></td></tr><?php
}

?>
	<div>
  <table width="100%" cellspacing="0">
        <thead class="print">
      <tr style="font-weight:bold">
            <td>Dato</td>
            <td>Stregkode</td>
            <td>Faktureret</td>
            <td>Længde</td>
            <td>Bredde</td>
            <td>Højde</td>
            <td>Services</td>
            <td>Pris</td>
          </tr>
    </thead>
        <tbody><tr>
		  <td colspan="8" style="background-color:#FFFFFF;page-break-after:avoid; page-break-inside:avoid"><h1>Erhvervspakker</h1></td>
		</tr><?php
			rows('E');
	?><tr>
		  <td colspan="8" style="background-color:#FFFFFF;page-break-before:always;page-break-after:avoid; page-break-inside:avoid"><h1>Privatpakker</h1></td>
		</tr> <?php
			rows('P');
	?><tr>
		  <td colspan="8" style="background-color:#FFFFFF;page-break-before:always;page-break-after:avoid; page-break-inside:avoid"><h1>Postopkrævningspakker</h1></td>
		</tr><?php
			rows('O');
		?>
	</tbody>
    </table>
</div>
</body>
</html>