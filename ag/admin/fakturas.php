<?php

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';

if(empty($_SESSION['_user'])) {
	//TDODO No login !!!
	$_SESSION['_user']['fullname'] = 'No one';
}
require_once '../inc/sajax.php';
require_once '../inc/config.php';
require_once '../inc/mysqli.php';

$GLOBALS['_config']['mysql_server'] = 'huntershouse.dk.mysql';
$GLOBALS['_config']['mysql_user'] = 'huntershouse_dk';
$GLOBALS['_config']['mysql_password'] = 'sabbBFab';
$GLOBALS['_config']['mysql_database'] = 'huntershouse_dk';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$sajax_request_type = 'POST';

if(!empty($_POST['m']) && !empty($_POST['y'])) {
	$where = " `date` >= '".$_POST['y']."-".$_POST['m']."-01'";
	$where .= " AND `date` <= '".$_POST['y']."-".$_POST['m']."-31'";
} elseif(!empty($_POST['y'])) {
	$where = " `date` >= '".$_POST['y']."-01-01'";
	$where .= " AND `date` <= '".$_POST['y']."-12-31'";
} else {
	$where = " `date` >= '".date('Y')."-01-01'";
	$where .= " AND `date` <= '".date('Y')."-12-31'";
}

if(!empty($_POST['department']))
	$where .= " AND `department` = '".$_POST['department']."'";

if(empty($_POST)) {
	$where .= " AND `clerk` = '".$_SESSION['_user']['fullname']."'";
} elseif(!empty($_POST['clerk']))
	$where .= " AND `clerk` = '".$_POST['clerk']."'";

if(empty($_POST) || (!empty($_POST['status']) && $_POST['status'] == 'activ'))
	$where .= " AND (`status` = 'new' OR `status` = 'locked' OR `status` = 'pbsok' OR `status` = 'pbserror')";
elseif(!empty($_POST['status']) && $_POST['status'] == 'inactiv')
	$where .= " AND (`status` != 'new' AND `status` != 'locked' AND `status` != 'pbsok' AND `status` != 'pbserror')";
elseif(!empty($_POST['status']) && $_POST['status'])
	$where .= " AND `status` = '".$_POST['status']."'";

if(!empty($_POST['name']))
	$where .= " AND `navn` LIKE '%".$_POST['name']."%'";

if(!empty($_POST['tlf']))
	$where .= " AND (`tlf1` LIKE '%".$_POST['tlf']."%' OR `tlf2` LIKE '%".$_POST['tlf']."%')";

if(empty($_POST)) {
	$_POST['y'] = date('Y');
	$_POST['clerk'] = $_SESSION['_user']['fullname'];
	$_POST['status'] = 'activ';
}

if(!empty($_POST['id']))
	$where = " `id` = '".$_POST['id']."'";


//echo("SELECT `momssats`, `premoms`, `values`, `quantities`, `fragt`, `id`, `status`, `clerk`, `amount`, `navn`, UNIX_TIMESTAMP(`date`) AS `date` FROM `fakturas` WHERE ".$where." ORDER BY `id` DESC");
$fakturas = $mysqli->fetch_array("SELECT `id`, `status`, `sendt`, `clerk`, `amount`, `navn`, `att`, `land`, `adresse`, `postbox`, `postnr`, `by`, `email`, `tlf1`, `tlf2`, UNIX_TIMESTAMP(`date`) AS `date` FROM `fakturas` WHERE ".$where." ORDER BY `id` DESC");


/*************Temp code for calcing amount*********
*
*
*
*
***************************************************/
/*
$fakturas = $mysqli->fetch_array("SELECT *, `id`, `status`, `clerk`, `amount`, `navn`, `att`, `land`, `adresse`, `postbox`, `postnr`, `by`, `email`, `tlf1`, `tlf2`, UNIX_TIMESTAMP(`date`) AS `date` FROM `fakturas` WHERE ".$where." ORDER BY `id` DESC");

$netto = 0;
	
function removeMoms($value) {
	global $faktura;
	global $key;
	return $value/(1+$faktura['momssats']);
}

foreach($fakturas as $key => $faktura) {
	$amount = 0;
	$faktura['quantities'] = explode('<', $faktura['quantities']);
	$faktura['values'] = explode('<', $faktura['values']);
	
	if($faktura['premoms'])
		$faktura['values'] = array_map('removeMoms', $faktura['values']);
	
	foreach($faktura['values'] as $valuekey => $value) {
		$amount += $value * $faktura['quantities'][$valuekey];
	}
	
	$fakturas[$key]['amount'] = $amount * (1+$faktura['momssats']) + $faktura['fragt'];
	$mysqli->query("UPDATE `fakturas` SET `amount` = '".$fakturas[$key]['amount']."' WHERE `fakturas`.`id` =".$fakturas[$key]['id']." LIMIT 1;");
}

/***************************************************/

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Faktura liste</title>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<link href="style/mainmenu.css" rel="stylesheet" type="text/css" />
<style type="text/css">
@charset "utf-8";

body {
	margin:0;
	border:0;
	padding:0;
}

body, table, input {
	font-family:Verdana, Geneva, sans-serif;
	font-size:11px;
}
thead * {
	font-weight:bold;
}
caption {
	font-size:14px;
	margin-top:15px;
}
img {
	border:0;
}
.altbc {
	background-color:#e6eff4;
}
#list tr:hover {
	background-color:#6CC;
}
#list td {
	padding:0;
}
a {
	color:#000;
	text-decoration:none;
	display:block;
	padding:1px;
}
.address {
	display:none;
	position:absolute;
	background-color:#FFF;
	padding:3px 7px;
	border:1px solid #CCC;
}
#list td:hover .address {
	display:block;
}
#list td .address:hover {
	display:none;
}
</style>
</head>
<body onload="$('loading').style.visibility = 'hidden';">
<div id="canvas">
<form action="" method="post"><table><tr>
	<td><?php echo(_('Id:')); ?></td><td><?php echo(_('År:')); ?></td><td><?php echo(_('Måned:')); ?></td><td><?php echo(_('Ekspedient:')); ?></td><td><?php echo(_('Status:')); ?></td></tr><tr><td>

    <input name="id" value="<?php if(!empty($_POST['id'])) echo $_POST['id']; ?>" size="4" /></td><td>

    <select name="y"><?php
	$oldest = $mysqli->fetch_array("SELECT UNIX_TIMESTAMP(`date`) AS `date` FROM `fakturas` ORDER BY `date` ASC LIMIT 1");
	
	if($oldest)
		$oldest = date('Y', $oldest[0]['date']);
	else
		$oldest = date('Y');
	
	for($i=$oldest;$i<date('Y')+1;$i++) {
		?><option value="<?php echo($i) ?>"<?php if(@$_POST['y'] == $i || (@$_POST['y'] == '' && date('Y') == $i)) echo(' selected="selected"'); ?>><?php echo($i) ?></option><?php
	}
	?></select></td><td>
    <select name="m">
        <option value=""<?php if(@!$_POST['m']) echo(' selected="selected"'); ?>>Alle</option>
        <option value="1"<?php if(@$_POST['m'] == '1') echo(' selected="selected"'); ?>>Jan</option>
        <option value="2"<?php if(@$_POST['m'] == '2') echo(' selected="selected"'); ?>>Feb</option>
        <option value="3"<?php if(@$_POST['m'] == '3') echo(' selected="selected"'); ?>>Mar</option>
        <option value="4"<?php if(@$_POST['m'] == '4') echo(' selected="selected"'); ?>>Apr</option>
        <option value="5"<?php if(@$_POST['m'] == '5') echo(' selected="selected"'); ?>>Maj</option>
        <option value="6"<?php if(@$_POST['m'] == '6') echo(' selected="selected"'); ?>>Jun</option>
        <option value="7"<?php if(@$_POST['m'] == '7') echo(' selected="selected"'); ?>>Jul</option>
        <option value="8"<?php if(@$_POST['m'] == '8') echo(' selected="selected"'); ?>>Aug</option>
        <option value="9"<?php if(@$_POST['m'] == '9') echo(' selected="selected"'); ?>>Sep</option>
        <option value="10"<?php if(@$_POST['m'] == '10') echo(' selected="selected"'); ?>>Oct</option>
        <option value="11"<?php if(@$_POST['m'] == '11') echo(' selected="selected"'); ?>>Nov</option>
        <option value="12"<?php if(@$_POST['m'] == '12') echo(' selected="selected"'); ?>>Dec</option>
    </select>
    
	<?php
	/*
    if(count($GLOBALS['_config']['email']) < 2)
    	echo('<span style="display:none">');
    ?></td><td>
    <select name="department">
        <option value=""<?php if(!$_POST['department']) echo(' selected="selected"'); ?>>Alle</option><?php
		foreach($GLOBALS['_config']['email'] as $email) {
	        ?><option<?php if($_POST['department'] == $email) echo(' selected="selected"'); ?>><?php echo($email); ?></option><?php
		}
    ?></select><?php
    if(count($GLOBALS['_config']['email']) < 2)
    	echo('</span>');
	*/
	
	
	$users = $mysqli->fetch_array("SELECT `fullname`, `name` FROM `users` ORDER BY `fullname` ASC");
	
    if(count($users) < 2)
    	echo('<span style="display:none">');
    ?></td><td>
    <select name="clerk">
        <option value=""<?php if(!$_POST['clerk']) echo(' selected="selected"'); ?>>Alle</option><?php
        foreach($users as $user) {
			//warning if a user name is a it could colide with all
	        ?><option<?php if($_POST['clerk'] == $user['fullname']) echo(' selected="selected"'); ?>><?php echo($user['fullname']); ?></option><?php
		}
    ?></select><?php
    if(count($users) < 2)
    	echo('</span>');
	
    ?></td><td>
    <select name="status">
        <option value=""<?php if(!$_POST['status']) echo(' selected="selected"'); ?>>Alle</option>
        <option value="activ"<?php if($_POST['status'] == 'activ') echo(' selected="selected"'); ?>>Aktuelle</option>
        <option value="inactiv"<?php if($_POST['status'] == 'inactiv') echo(' selected="selected"'); ?>>Afsluttet</option>
        <option value="new"<?php if($_POST['status'] == 'new') echo(' selected="selected"'); ?>>Ny</option>
        <option value="locked"<?php if($_POST['status'] == 'locked') echo(' selected="selected"'); ?>>Låst</option>
        <option value="pbsok"<?php if($_POST['status'] == 'pbsok') echo(' selected="selected"'); ?>>Klar</option>
        <option value="accepted"<?php if($_POST['status'] == 'accepted') echo(' selected="selected"'); ?>>Ekspederede</option>
        <option value="giro"<?php if($_POST['status'] == 'giro') echo(' selected="selected"'); ?>>Giro</option>
        <option value="cash"<?php if($_POST['status'] == 'cash') echo(' selected="selected"'); ?>>Kontant</option>
        <option value="pbserror"<?php if($_POST['status'] == 'pbserror') echo(' selected="selected"'); ?>>Fejl</option>
        <option value="canceled"<?php if($_POST['status'] == 'canceled') echo(' selected="selected"'); ?>>Annulleret</option>
        <option value="rejected"<?php if($_POST['status'] == 'rejected') echo(' selected="selected"'); ?>>Afvist</option>
    </select></td><td><input type="submit" value="Hent" /></td></tr></table>
    
</form>
<table style="width:100%; margin:0 0 113px 0">
    <thead>
        <tr>
            <td style="width:16px;"></td>
            <td>Id</td>
            <td>Oprettet</td>
            <?php if(empty($_POST['clerk'])) { ?><td>Ansvarlige</td><?php } ?>
            <td>Beløb</td>
            <td>Modtager</td>
        </tr>
    </thead>
    <tbody id="list"><?php
		foreach($fakturas as $i => $faktura) { ?><tr<?php
				if($i%2==0)
					echo(' class="altbc"'); ?>>
            <td style="text-align:center"><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php
				if($faktura['status'] == 'new')
					echo('<img src="/admin/images/table.png" alt="Ny" title="Ny" />');
				elseif($faktura['status'] == 'locked' && $faktura['sendt'])
					echo('<img src="/admin/images/email_go.png" alt="Sendt" title="Sendt til kunden" />');
				elseif($faktura['status'] == 'locked')
					echo('<img src="/admin/images/lock.png" alt="Låst" title="Låst" />');
				elseif($faktura['status'] == 'pbsok')
					echo('<img src="/admin/images/money.png" alt="Klar" title="Klar" />');
				elseif($faktura['status'] == 'accepted')
					echo('<img src="/admin/images/email.png" alt="Ekspederede" title="Ekspederede" />');
				elseif($faktura['status'] == 'giro')
					echo('<img src="/admin/images/email.png" alt="Giro" title="Giro" />');
				elseif($faktura['status'] == 'cash')
					echo('<img src="/admin/images/email.png" alt="Kontant" title="Kontant" />');
				elseif($faktura['status'] == 'pbserror')
					echo('<img src="/admin/images/error.png" alt="Fejl" title="Fejl" />');
				elseif($faktura['status'] == 'canceled')
					echo('<img src="/admin/images/bin.png" alt="Annulleret" title="Annulleret" />');
				elseif($faktura['status'] == 'rejected')
					echo('<img src="/admin/images/bin.png" alt="Afvist" title="Afvist" />');
					
				//Efterkrav
				//Bank
				//Giro
			?></a></td>
            <td style="text-align:right"><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php echo($faktura['id']); ?></a></td>
            <td style="text-align:right"><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php echo(date('j/m/y', $faktura['date'])); ?></a></td>
            <?php if(!$_POST['clerk']) { ?><td><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php echo($faktura['clerk']); ?></a></td><?php } ?>
            <td style="text-align:right"><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php echo(number_format($faktura['amount'], 2, ',', '.')); ?></a></td>
            <td><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php if(!$faktura['navn'] && $faktura['email']) echo($faktura['email']); else echo($faktura['navn']); ?></a><div class="address"><?php
            echo($faktura['navn'].'<br/>'.
				'Att.: '.$faktura['att'].'<br/>'.
				$faktura['adresse'].'<br/>'.
				$faktura['postbox'].'<br/>'.
				$faktura['postnr'].' '.$faktura['by'].'<br/>');
				if($faktura['land']) {
					require '../inc/countries.php';
					echo($countries[$faktura['land']].'<br/>');
				}
				echo($faktura['email'].'<br/>'.
				$faktura['tlf1'].'<br/>'.
				$faktura['tlf2']);
			?></div></td>
        </tr><?php } ?>
    </tbody>
</table>
</div>
<?php
$activityButtons[] = '<li><a href="faktura.php?function=new"><img src="images/table_add.png" width="16" height="16" alt="" title="Opret ny" /> Opret ny</a></li>';
$activityButtons[] = '<li><a href="fakturasearch.php"><img src="images/magnifier.png" width="16" height="16" alt="" title="Advanceret søgning" /> Søgning</a></li>';

require 'mainmenu.php';
?>
</body>
</html>
