<?php

require_once("clsWorkDays.php");

if(!isset($_GET['y'])) {
	$_GET['y'] = date('Y');
	$_GET['m'] = date('n');
}

require_once("calcpakkepris".$_GET['y'].".php");
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pakke priser</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
	table a {
		color:#000000;
	}
</style>
<script type="text/javascript" src="/javascript/json2.stringify.js"></script> 
<script type="text/javascript" src="/javascript/json_stringify.js"></script>
<script type="text/javascript" src="/javascript/json_parse_state.js"></script> 
<script type="text/javascript" src="/javascript/sajax.js"></script> 
<script type="text/javascript" src="javascript.js"></script>
<script type="text/javascript" src="liste.js"></script>
</head>
<body>
<div id="loading" style="display:none;"><img src="load.gif" width="228" height="144" alt="" title="Komunikere med post danmark..." /></div>
<?php
include('menu.html');
require_once("../inc/mysqli.php");
require_once("../inc/config.php");
require_once("config.php");
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

if(isset($_GET['delete']))
	$mysqli->query('UPDATE `post` SET deleted = 1 WHERE id = '.$_GET['delete']);

function rows($where) {
	$where .= ' AND deleted = 0';
	global $mysqli;

?>
        <tr style="font-weight:bold">
          <td></td>
          <td>Id</td>
          <td>Ordre</td>
          <td>Navn</td>
          <td>Adresse</td>
          <td>Post nr.</td>
          <td>Tlf</td>
          <td>Dag</td>
          <td>Fragtpris</td>
        </tr><?php
$post = $mysqli->fetch_array('SELECT *, UNIX_TIMESTAMP(`formDate`) as date FROM `post`'.$where.' ORDER BY `formDate` DESC, `id` DESC');
$post_nr = count($post);

for($i=0;$i<$post_nr;$i++) {
	if($post[$i]['ub'] == 'false') {
		$fragt = pakkepris($post[$i]['pd_height'], $post[$i]['pd_width'], $post[$i]['pd_length'], $post[$i]['pd_weight'], $post[$i]['optRecipType'], $post[$i]['ss1'], $post[$i]['ss46'], $post[$i]['ss5amount'], true);
		@$totalPorto += $fragt;
		$specialFragt = pakkepris($post[$i]['pd_height'], $post[$i]['pd_width'], $post[$i]['pd_length'], max(5, $post[$i]['pd_weight']), $post[$i]['optRecipType'], $post[$i]['ss1'], $post[$i]['ss46'], $post[$i]['ss5amount'], true);
		if($specialFragt != 57)
			$specialFragt = ceil($specialFragt*0.2)/0.2;
	}
	?>
    <tr<?php
	if($i % 2)
		echo(' style="background-color:#999999"');
	else
		echo(' style="background-color:#dedede"');
?>>
    <td style="text-align:left"><?php
	
		if($post[$i]['token'] > 1) {
			require("config.php");
			?><a onclick="return changeUser(<?php echo($post[$i]['formSenderID']); ?>);" href="http://www.postdanmark.dk/pfs/PfsLabelServlet?buttonPressed=Print&clientID=<?php echo $clientID; ?>&userID=admin&token=&programID=&sessionID=&accessCode=&exTime=&forsID=<?php echo($post[$i]['token']); ?>" target="_blank"><img height="16" width="16" alt="PDF" title="Hent PDF" src="pdf-icon.gif" style="border:0" /></a> <?php
		}
		if($post[$i]['STREGKODE']) {
	        ?><a href="http://www.postdanmark.dk/tracktrace/TrackTrace.do?i_lang=IND&amp;i_stregkode=<?php echo($post[$i]['STREGKODE']); ?>" target="_blank"><?php
			if($post[$i]['pd_return'] == 'true' && $post[$i]['pd_arrived'] == 'true') {
				?><img height="16" width="16" alt="T&T: <?php echo($post[$i]['STREGKODE']); ?>" title="Track & Trace - Pakken er sendt retur: <?php echo($post[$i]['STREGKODE']); ?>" src="magifier_zoom_out.png" style="border:0" /><?php
			} elseif($post[$i]['pd_arrived'] == 'true') {
				?><img height="16" width="16" alt="T&T: <?php echo($post[$i]['STREGKODE']); ?>" title="Track & Trace - Pakken er ankomet: <?php echo($post[$i]['STREGKODE']); ?>" src="magnifier_zoom_in.png" style="border:0" /><?php
			} else {
				$clsWorkDays = new clsWorkDays;
				$clsWorkDays->clsWorkDays();
				$WorkDays = $clsWorkDays->days_diff($post[$i]['formDate'], date("Y-m-d"));
				if($post[$i]['pd_return'])
					$delaydate = 2;
				if(($WorkDays > 14+$delaydate && $post[$i]['optRecipType'] == 'O') || ($WorkDays > 7+$delaydate && $post[$i]['optRecipType'] != 'O')) {
					if($post[$i]['reklmation'] == 'true') {
						?><img height="16" width="16" alt="T&T! <?php echo($post[$i]['STREGKODE']); ?>" title="Track & Trace - Pakken er sendt til efterlysning: <?php echo($post[$i]['STREGKODE']); ?>" src="magnifier_error.png" style="border:0" /><?php
					} else {
						?><img height="16" width="16" alt="T&T! <?php echo($post[$i]['STREGKODE']); ?>" title="Track & Trace - Pakken skal efterlyses: <?php echo($post[$i]['STREGKODE']); ?>" src="magnifier_error.png" style="border:0" /><?php
					}
				} else {
					?><img height="16" width="16" alt="T&T: <?php echo($post[$i]['STREGKODE']); ?>" title="Track & Trace: <?php echo($post[$i]['STREGKODE']); ?>" src="magnifier.png" style="border:0" /><?php
				}
			}
            ?></a> <?php
		}
		//Pakke type
		if($post[$i]['optRecipType'] == 'P') {
			if($post[$i]['ss2'] == 'true') {
				?><img src="package_add.png" width="16" height="16" alt="PV" title="Volume" /> <?php
			} else {
	        	?><img src="package.png" width="16" height="16" alt="P" title="Privat" /> <?php
			}
		} elseif($post[$i]['optRecipType'] == 'E') {
	        ?><img src="lorry.png" width="16" height="16" alt="E" title="Erhverv" /> <?php
		} elseif($post[$i]['optRecipType'] == 'O') {
	        ?><img src="money.png" width="16" height="16" alt="O" title="Post opkrævning: <?php echo(str_replace(',00', ',-', number_format($post[$i]['recPoValue'], 2, ',', '.'))); ?>" /> <?php
		}
		
		//TODO convert strings to gettext from here and up
		
		//Forsigtig
		if($post[$i]['ss1'] == 'true') {
	        ?><img src="drink_empty.png" width="16" height="16" alt="F" title="<?php echo(_('Forsigtig')); ?>" /> <?php
		}
		//Lørdags express
		if($post[$i]['ss46'] == 'true') {
	        ?><img src="lightning.png" width="16" height="16" alt="L" title="<?php echo(_('Lørdags express')); ?>" /> <?php
		}
		//Hvad er værdi
		if($post[$i]['ss5amount']) {
	        ?><img src="coins.png" width="16" height="16" alt="V" title="<?php echo(_('Værdi:')); ?> <?php echo str_replace(',00', ',-', number_format($post[$i]['ss5amount'], 2, ',', '.')); ?>" /> <?php
		}
		//Er porto sat forkert
		if($post[$i]['ub'] == 'false') {
			if(($post[$i]['pd_weight'] && $post[$i]['ss2'] == 'false') || (($post[$i]['optRecipType'] != 'P' || $post[$i]['ss2'] == 'true') && $post[$i]['pd_height'] && $post[$i]['pd_width'] && $post[$i]['pd_length'])) {
				
				if($specialFragt != 57 && $fragt != 54 && $specialFragt != $post[$i]['porto'] && $fragt != $post[$i]['porto']) {
					if($post[$i]['porto']-$specialFragt > 0) {
						?><img src="error_add.png" width="16" height="16" alt="!+" title="<?php echo(_('Fragt stemmer ikke:')); ?> <?php echo str_replace(',00', ',-', number_format($post[$i]['porto']-$specialFragt, 2, ',', '.')); ?>" /> <?php
					} else {
						?><img src="error_delete.png" width="16" height="16" alt="!-" title="<?php echo(_('Fragt stemmer ikke:')); ?> <?php echo str_replace(',00', ',-', number_format($post[$i]['porto']-$specialFragt, 2, ',', '.')); ?>" /> <?php
					}
				} elseif($specialFragt != $post[$i]['porto'] && $fragt != $post[$i]['porto']) {
					if($post[$i]['porto']-$fragt > 0) {
						?><img src="error_add.png" width="16" height="16" alt="!+" title="<?php echo(_('Fragt stemmer ikke:')); ?> <?php echo str_replace(',00', ',-', number_format($post[$i]['porto']-$fragt, 2, ',', '.')); ?>" /> <?php
					} else {
						?><img src="error_delete.png" width="16" height="16" alt="!-" title="<?php echo(_('Fragt stemmer ikke:')); ?> <?php echo str_replace(',00', ',-', number_format($post[$i]['porto']-$fragt, 2, ',', '.')); ?>" /> <?php
					}
				}
			//Er volume blevet sat ved fejl
				if($post[$i]['ss2'] == 'true' && !calcvolume($post[$i]['pd_height'], $post[$i]['pd_width'], $post[$i]['pd_length'])) {
					?><img src="brick_add.png" width="16" height="16" alt="!" title="<?php echo(_('Ikke volume sendt som volume')); ?>" /> <?php
				} elseif($post[$i]['ss2'] == 'false' && $post[$i]['optRecipType'] == 'P' && calcvolume($post[$i]['pd_height'], $post[$i]['pd_width'], $post[$i]['pd_length'])) {
					?><img src="brick_delete.png" width="16" height="16" alt="!" title="<?php echo(_('Volume sendt som ikke volume')); ?>" /> <?php
				} elseif($post[$i]['optRecipType'] != 'P' && $post[$i]['pd_height'] && $post[$i]['pd_width'] && $post[$i]['pd_length'] && $post[$i]['height'] && $post[$i]['width'] && $post[$i]['length']) {
					if(ceil($post[$i]['pd_height'] * $post[$i]['pd_width'] * $post[$i]['pd_length'] / 4000*0.2)/0.2 > ceil($post[$i]['height'] * $post[$i]['width'] * $post[$i]['length'] / 4000*0.2)/0.2) {
						?><img src="brick_add.png" width="16" height="16" alt="!" title="<?php echo(sprintf(_('Forkert volumevægt: %f kg'), round($post[$i]['pd_height'] * $post[$i]['pd_width'] * $post[$i]['pd_length'] / 4000 - $post[$i]['height'] * $post[$i]['width'] * $post[$i]['length'] / 4000))); ?>" /> <?php
					} elseif(ceil($post[$i]['pd_height'] * $post[$i]['pd_width'] * $post[$i]['pd_length'] / 4000*0.2)/0.2 > ceil($post[$i]['height'] * $post[$i]['width'] * $post[$i]['length'] / 4000*0.2)/0.2) {
						?><img src="brick_delete.png" width="16" height="16" alt="!" title="<?php echo(sprintf(_('Forkert volumevægt: %f kg'), round($post[$i]['pd_height'] * $post[$i]['pd_width'] * $post[$i]['pd_length'] / 4000 - $post[$i]['height'] * $post[$i]['width'] * $post[$i]['length'] / 4000))); ?>" /> <?php
					}
				}
			} else {
				if(($post[$i]['pd_arrived'] == 'true' && $post[$i]['pd_weight'] == 0) || ($post[$i]['ss2'] == 'true' && $post[$i]['pd_height'] == 0 && ($post[$i]['pd_arrived'] == 'true' || $post[$i]['pd_weight'] != 0))) {
					?><img src="error.png" width="16" height="16" alt="!" title="<?php echo(_('Info fra Track &amp; Trace utilstrækkelig')); ?>" /> <?php
				} else {
					?><a href="/syncpost.php?id=<?php echo($post[$i]['id']); ?>"><img style="border:0" src="arrow_refresh.png" width="16" height="16" alt="!" title="<?php echo(_('Mangler info fra Track &amp; Trace')); ?>" /></a> <?php
					?><a href="?delete=<?php echo($post[$i]['id']); ?>"><img style="border:0" src="bin.png" width="16" height="16" alt="X" title="<?php echo(_('Slet')); ?>" /></a> <?php
				}
			}
			if($post[$i]['pd_weight'] > 0 && $post[$i]['weight'] > 0) {
				if(ceil($post[$i]['pd_weight']*0.2)/0.2 < ceil($post[$i]['weight']*0.2)/0.2) {
					?><img src="tag_blue_add.png" width="16" height="16" alt="!" title="<?php echo(sprintf(_('Forkert vægt: %f kg'), $post[$i]['pd_weight'] - $post[$i]['weight'])); ?>" /> <?php
				} elseif(ceil($post[$i]['pd_weight']*0.2)/0.2 > ceil($post[$i]['weight']*0.2)/0.2) {
					?><img src="tag_blue_delete.png" width="16" height="16" alt="!" title="<?php echo(sprintf(_('Forkert vægt: %f kg'), $post[$i]['pd_weight'] - $post[$i]['weight'])); ?>" /> <?php
				}
			}
		} elseif(!$post[$i]['pd_weight'] && !$post[$i]['pd_height'] && !$post[$i]['pd_width'] && !$post[$i]['pd_length'] && $post[$i]['pd_return'] == 'false' && $post[$i]['pd_arrived'] == 'false') {
			?><a href="/syncpost.php?id=<?php echo($post[$i]['id']); ?>"><img style="border:0" src="arrow_refresh.png" width="16" height="16" alt="!" title="<?php echo(_('Mangler info fra Track &amp; Trace')); ?>" /></a> <?php
			?><a href="?delete=<?php echo($post[$i]['id']); ?>"><img style="border:0" src="bin.png" width="16" height="16" alt="X" title="<?php echo(_('Slet')); ?>" /></a> <?php
		}
		?>
        </td>
    <td style="text-align:right"><?php echo($post[$i]['id']); ?></td>
    <td style="text-align:right"><?php if($post[$i]['fakturaid']) { ?><a href="/admin/faktura.php?id=<?php echo($post[$i]['fakturaid']); ?>"><?php echo($post[$i]['fakturaid']); ?></a><?php } ?></td>
    <td><?php echo($post[$i]['recName1']); ?></td>
    <td><?php echo($post[$i]['recAddress1']); ?></td>
    <td style="text-align:right"><?php echo($post[$i]['recZipCode']); ?></td>
    <td><?php echo($post[$i]['recipientID']); ?></td>
    <td><?php echo($post[$i]['formDate']); ?></td>
    <td style="text-align:right"><?php  echo($post[$i]['ub'] == 'false' ? str_replace(',00', ',-', number_format($post[$i]['porto'], 2, ',', '.')) : 'UB'); ?></td>
</tr><?php
}

$total = $mysqli->fetch_array('SELECT sum(`recPoValue`) AS po, sum(`porto`) AS gebyr FROM `post`'.$where.' AND ub = \'false\' AND deleted = 0');
@$GLOBALS['totalPorto'] += $totalPorto;
?><tr style="font-weight:bold">
    <td colspan="8"><table cellpadding="0" cellspacing="0" style="width:100%"><tr><td><?php echo(_('I alt:')); ?></td> <td><?php echo(_('Postopkrævning:')); ?> <?php echo(str_replace(',00', ',-', number_format($total[0]['po'], 2, ',', '.'))) ?></td> <td><?php echo(_('Opkrævet porto:')); ?> <?php echo(str_replace(',00', ',-', number_format($total[0]['gebyr'], 2, ',', '.'))) ?></td> <td><?php echo(_('Porto:')); ?> <?php echo(str_replace(',00', ',-', number_format(@$totalPorto, 2, ',', '.'))) ?></td></tr></table></td>
</tr><?php
}

$where = ' WHERE `token` != \'\'';
if(@$_GET['y'] && @$_GET['m']) {
	$where .= ' AND `formDate` >= \''.$_GET['y'].'-'.$_GET['m'].'-01\'';
	$where .= ' AND `formDate` <= \''.$_GET['y'].'-'.$_GET['m'].'-31\'';
} elseif(@$_GET['y'] && !@$_GET['m']) {
	$where .= ' AND `formDate` >= \''.$_GET['y'].'-01-01\'';
	$where .= ' AND `formDate` <= \''.$_GET['y'].'-12-31\'';
}
if(@$_GET['user'])
	$where .= ' AND `formSenderID` = \''.@$_GET['user'].'\'';
if(@$_GET['optRecipType'] && @$_GET['optRecipType'] != 'ub')
	$where .= ' AND `optRecipType` = \''.@$_GET['optRecipType'].'\'';
if(@$_GET['optRecipType'] == 'ub')
	$where .= ' AND `ub` = \'true\'';
if(@$_GET['status'] == 'pd_arrived_true')
	$where .= ' AND `pd_arrived` = \'true\'';
if(@$_GET['status'] == 'pd_arrived_false')
	$where .= ' AND `pd_arrived` = \'false\'';
if(@$_GET['status'] == 'pd_return_true')
	$where .= ' AND `pd_return` = \'true\'';
if(@$_GET['status'] == 'pd_return_false')
	$where .= ' AND `pd_return` = \'false\'';
if(@$_GET['id'])
	$where .= ' AND `id` = \''.@$_GET['id'].'\'';
if(@$_GET['recName1'])
	$where .= ' AND `recName1` LIKE \'%'.@$_GET['recName1'].'%\'';
if(@$_GET['recAddress1'])
	$where .= ' AND `recAddress1` = \''.@$_GET['recAddress1'].'\'';
if(@$_GET['recZipCode'])
	$where .= ' AND `recZipCode` = \''.@$_GET['recZipCode'].'\'';
if(@$_GET['recipientID'])
	$where .= ' AND `recipientID` = \''.@$_GET['recipientID'].'\'';
if(@$_GET['STREGKODE'])
	$where .= ' AND `STREGKODE` LIKE \'%'.@$_GET['STREGKODE'].'%\'';
if(@$_GET['recPoValue'])
	$where .= ' AND `recPoValue` = \''.@$_GET['recPoValue'].'\'';
	
	

if(@$_GET['multirecp']) {
	$where .= ' AND (';
	$multirecp = $mysqli->fetch_array('SELECT `recipientID`, count( * ) AS n FROM post WHERE `recipientID` != \'\' AND deleted = 0 GROUP BY `recipientID` HAVING n >1');
	for($i=0;$i<count($multirecp);$i++) {
		if($i > 0)
			$where .= ' OR ';
		$where .= 'recipientID = '.$multirecp[$i]['recipientID'];
	}
	$where .= ')';
}

?>
<div style="width:650px; margin:10px 0 0 180px"> <img height="50" alt="" src="http://www.postdanmark.dk/pfs/grafik/pakker.gif" style="float:right" />
  <h2 style="padding:25px 0 0 0; margin:0"><?php echo(_('Forsendelser')); ?></h2>
  <hr />
  <br />
  <form action="" method="get" style="margin:0"><table><tr>
  <td><?php echo(_('Afsender')); ?></td>
  <td><?php echo(_('År')); ?></td>
  <td><?php echo(_('Måned')); ?></td>
  <td><?php echo(_('Pakke type')); ?></td>
  <td><?php echo(_('Status')); ?></td>
  <td><?php echo(_('Id')); ?></td>
  <td><?php echo(_('Modtager')); ?></td>
  <td><?php echo(_('Tlf')); ?></td>
  <td><?php echo(_('Stregkode')); ?></td>
  <td><?php echo(_('Beløb')); ?></td>
  </tr><tr><td>
    <select name="user">
      <option value=""<?php if(@$_GET['user'] == '') { ?> selected="selected"<?php } ?>><?php echo(_('Alle')); ?></option><?php
      foreach($brugere as $id => $navn) {
      	?><option value="<?php echo($id); ?>"<?php if(@$_GET['user'] == $id) { ?> selected="selected"<?php } ?>><?php echo($navn); ?></option><?php
      }
    ?></select></td><td>
    <select name="y">
      <option value="2008"<?php if(@$_GET['y'] == 2008) { ?> selected="selected"<?php } ?>>2008</option><?php
		for($i=2009;$i<date('Y')+1;$i++) {
			?><option value="<?php echo($i) ?>"<?php if(@$_GET['y'] == $i) { ?> selected="selected"<?php } ?>><?php echo($i) ?></option><?php
		}
 ?>
    </select></td><td>
    <select name="m">
      <option value=""<?php if(@$_GET['m'] == '') { ?> selected="selected"<?php } ?>><?php echo(_('Alle')); ?></option>
      <option value="1"<?php if(@$_GET['m'] == 1) { ?> selected="selected"<?php } ?>><?php echo(_('Jan')); ?></option>
      <option value="2"<?php if(@$_GET['m'] == 2) { ?> selected="selected"<?php } ?>><?php echo(_('Feb')); ?></option>
      <option value="3"<?php if(@$_GET['m'] == 3) { ?> selected="selected"<?php } ?>><?php echo(_('Mar')); ?></option>
      <option value="4"<?php if(@$_GET['m'] == 4) { ?> selected="selected"<?php } ?>><?php echo(_('Apr')); ?></option>
      <option value="5"<?php if(@$_GET['m'] == 5) { ?> selected="selected"<?php } ?>><?php echo(_('Maj')); ?></option>
      <option value="6"<?php if(@$_GET['m'] == 6) { ?> selected="selected"<?php } ?>><?php echo(_('Jun')); ?></option>
      <option value="7"<?php if(@$_GET['m'] == 7) { ?> selected="selected"<?php } ?>><?php echo(_('Jul')); ?></option>
      <option value="8"<?php if(@$_GET['m'] == 8) { ?> selected="selected"<?php } ?>><?php echo(_('Aug')); ?></option>
      <option value="9"<?php if(@$_GET['m'] == 9) { ?> selected="selected"<?php } ?>><?php echo(_('Sep')); ?></option>
      <option value="10"<?php if(@$_GET['m'] == 10) { ?> selected="selected"<?php } ?>><?php echo(_('Oct')); ?></option>
      <option value="11"<?php if(@$_GET['m'] == 11) { ?> selected="selected"<?php } ?>><?php echo(_('Nov')); ?></option>
      <option value="12"<?php if(@$_GET['m'] == 12) { ?> selected="selected"<?php } ?>><?php echo(_('Dec')); ?></option>
    </select></td><td>
    <select name="optRecipType">
      <option value=""<?php if(@$_GET['optRecipType'] == '') { ?> selected="selected"<?php } ?>><?php echo(_('Alle')); ?></option>
      <option value="P"<?php if(@$_GET['optRecipType'] == 'P') { ?> selected="selected"<?php } ?>><?php echo(_('Privat')); ?></option>
      <option value="E"<?php if(@$_GET['optRecipType'] == 'E') { ?> selected="selected"<?php } ?>><?php echo(_('Erhverv')); ?></option>
      <option value="O"<?php if(@$_GET['optRecipType'] == 'O') { ?> selected="selected"<?php } ?>><?php echo(_('PO')); ?></option>
      <option value="ub"<?php if(@$_GET['optRecipType'] == 'ub') { ?> selected="selected"<?php } ?>><?php echo(_('UB')); ?></option>
    </select></td><td>
    <select name="status">
      <option value=""<?php if(@$_GET['status'] == '') { ?> selected="selected"<?php } ?>><?php echo(_('Alle')); ?></option>
      <option value="pd_arrived_true"<?php if(@$_GET['status'] == 'pd_arrived_true') { ?> selected="selected"<?php } ?>><?php echo(_('Levert')); ?></option>
      <option value="pd_arrived_false"<?php if(@$_GET['status'] == 'pd_arrived_false') { ?> selected="selected"<?php } ?>><?php echo(_('Ikke levert')); ?></option>
      <option value="pd_return_true"<?php if(@$_GET['status'] == 'pd_return_true') { ?> selected="selected"<?php } ?>><?php echo(_('Sendt retur')); ?></option>
      <option value="pd_return_false"<?php if(@$_GET['status'] == 'pd_return_false') { ?> selected="selected"<?php } ?>><?php echo(_('Ikke retur')); ?></option>

    </select></td><td>
    <input name="id" size="3" style="width:auto" value="<?php echo(@$_GET['id']); ?>" /></td><td>
    <input name="recName1" style="width:auto" value="<?php echo(@$_GET['recName1']); ?>" /></td><?php /* ?><td>
    <input name="recAddress1" style="width:auto" value="<?php echo(@$_GET['recAddress1']); ?>" /></td><td>
    <input name="recZipCode" size="4" style="width:auto" value="<?php echo(@$_GET['recZipCode']); ?>" /><?php */ ?></td><td>
    <input name="recipientID" size="10" style="width:auto" value="<?php echo(@$_GET['recipientID']); ?>" /></td><td>
    <input name="STREGKODE" size="13" maxlength="13" style="width:auto" value="<?php echo(@$_GET['STREGKODE']); ?>" /></td><td>
    <input name="recPoValue" size="5" maxlength="5" style="width:auto" value="<?php echo(@$_GET['recPoValue']); ?>" /></td></tr>
   <tr><td><input type="submit" value="<?php echo(_('Hent')); ?>" style="width:auto" /></td></tr>
  </form></table><br />
<a href="/syncpost.php?y=<?php echo(@$_GET['y']) ?>&amp;m=<?php echo(@$_GET['m']) ?>" style="color:#000000"><img src="arrow_refresh.png" alt="" width="16" height="16" title="" style="border:0" /> <?php echo(_('med Track &amp; Trace')); ?></a><br />
<a href="postregning.php?y=<?php echo(@$_GET['y']) ?>&amp;m=<?php echo(@$_GET['m']) ?>" style="color:#000000"><img src="application_view_list.png" alt="" width="16" height="16" title="" style="border:0" /> <?php echo(_('Vis som postdanmark regning')); ?></a>
<a href="spec.php?y=<?php echo(@$_GET['y']) ?>&amp;m=<?php echo(@$_GET['m']) ?>" style="color:#000000"><img src="application_view_list.png" alt="" width="16" height="16" title="" style="border:0" /> <?php echo(_('Vis som postdanmark Fakturaspecifikation')); ?></a>
  <table><?php
  
  foreach($brugere as $id => $navn) {
	  	if(@$_GET['user'] == $id) {
			?><tr><td colspan="9"><h2><br /><?php echo($navn); ?></h2></td></tr><?php
			rows($where);
	   }
  }
  if(!@$_GET['user']) {
  	foreach($brugere as $id => $navn) {
		?><tr><td colspan="9"><h2><br /><?php echo($navn); ?></h2></td></tr><?php
		  rows($where.' AND `formSenderID` = \''.$id.'\'');
	   }
   }


/*	  $total = $mysqli->fetch_array('SELECT sum(`recPoValue`) AS po, sum(`porto`) AS gebyr FROM `post` WHERE deleted = 0 AND `token` != \'\' AND `formDate` >= \''.@$_GET['y'].'-'.@$_GET['m'].'-01\' AND `formDate` <= \''.@$_GET['y'].'-'.@$_GET['m'].'-31\'');
?><tr><td colspan="9"><br /><h2>I alt</h2></td></tr>
    <tr style="font-weight:bold"><td colspan="8">I alt - Postopkrævning: <?php echo(number_format($total[0]['po'], 2, ',', '.')) ?>,- Opkrævet porto: <?php echo(number_format($total[0]['gebyr'], 2, ',', '.')) ?>,- Porto: <?php echo(number_format($GLOBALS['totalPorto'], 2, ',', '.')) ?>,-</td><?php
*/  ?></table>
</div>
</body>
</html>
