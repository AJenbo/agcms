<?php

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';

if(empty($_SESSION['_user'])) {
	//TDODO No login !!!
	$_SESSION['_user']['fullname'] = _('No one');
}
require_once '../inc/sajax.php';
require_once '../inc/config.php';
require_once '../inc/mysqli.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$sajax_request_type = 'POST';

$where = array();

if(!empty($_POST['m']) && !empty($_POST['y'])) {
	$where[] = "`date` >= '".$_POST['y']."-".$_POST['m']."-01'";
	$where[] = "`date` <= '".$_POST['y']."-".$_POST['m']."-31'";
} elseif(!empty($_POST['y'])) {
	$where[] = "`date` >= '".$_POST['y']."-01-01'";
	$where[] = "`date` <= '".$_POST['y']."-12-31'";
}

if(!empty($_POST['department']))
	$where[] = "`department` = '".$_POST['department']."'";

if(empty($_POST) && $_SESSION['_user']['access'] != 1) {
	$where[] = "(`clerk` = '".$_SESSION['_user']['fullname']."' OR `clerk` = '')";
} elseif(!empty($_POST['clerk']))
	$where[] = "(`clerk` = '".$_POST['clerk']."' OR `clerk` = '')";

if(empty($_POST) || (!empty($_POST['status']) && $_POST['status'] == 'activ'))
	$where[] = "(`status` = 'new' OR `status` = 'locked' OR `status` = 'pbsok' OR `status` = 'pbserror')";
elseif(!empty($_POST['status']) && $_POST['status'] == 'inactiv')
	$where[] = "(`status` != 'new' AND `status` != 'locked' AND `status` != 'pbsok' AND `status` != 'pbserror')";
elseif(!empty($_POST['status']) && $_POST['status'])
	$where[] = "`status` = '".$_POST['status']."'";

if(!empty($_POST['name']))
	$where[] = "`navn` LIKE '%".$_POST['name']."%'";

if(!empty($_POST['tlf']))
	$where[] = "(`tlf1` LIKE '%".$_POST['tlf']."%' OR `tlf2` LIKE '%".$_POST['tlf']."%')";

$where = implode(' AND ', $where);

if(empty($_POST)) {
	$_POST['y'] = date('Y');
	$_POST['clerk'] = $_SESSION['_user']['fullname'];
	$_POST['status'] = 'activ';
}

if(!empty($_POST['id']))
	$where = " `id` = '".$_POST['id']."'";

//echo("SELECT `momssats`, `premoms`, `values`, `quantities`, `fragt`, `id`, `status`, `clerk`, `amount`, `navn`, UNIX_TIMESTAMP(`date`) AS `date` FROM `fakturas` WHERE ".$where." ORDER BY `id` DESC");
$fakturas = $mysqli->fetch_array("SELECT `id`, `status`, `sendt`, `clerk`, `amount`, `navn`, `att`, `land`, `adresse`, `postbox`, `postnr`, `by`, `email`, `tlf1`, `tlf2`, UNIX_TIMESTAMP(`date`) AS `date` FROM `fakturas` WHERE ".$where." ORDER BY `id` DESC");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo(_('Invoice list')); ?></title>
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
	<td><?php echo(_('ID:')); ?></td><td><?php echo(_('Year:')); ?></td><td><?php echo(_('Month:')); ?></td><td><?php echo(_('Clerk:')); ?></td><td><?php echo(_('Status:')); ?></td></tr><tr><td>

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
        <option value=""<?php if(@!$_POST['m']) echo(' selected="selected"'); ?>><?php echo(_('All')); ?></option>
        <option value="1"<?php if(@$_POST['m'] == '1') echo(' selected="selected"'); ?>><?php echo(_('Jan')); ?></option>
        <option value="2"<?php if(@$_POST['m'] == '2') echo(' selected="selected"'); ?>><?php echo(_('Feb')); ?></option>
        <option value="3"<?php if(@$_POST['m'] == '3') echo(' selected="selected"'); ?>><?php echo(_('Mar')); ?></option>
        <option value="4"<?php if(@$_POST['m'] == '4') echo(' selected="selected"'); ?>><?php echo(_('Apr')); ?></option>
        <option value="5"<?php if(@$_POST['m'] == '5') echo(' selected="selected"'); ?>><?php echo(_('May')); ?></option>
        <option value="6"<?php if(@$_POST['m'] == '6') echo(' selected="selected"'); ?>><?php echo(_('Jun')); ?></option>
        <option value="7"<?php if(@$_POST['m'] == '7') echo(' selected="selected"'); ?>><?php echo(_('Jul')); ?></option>
        <option value="8"<?php if(@$_POST['m'] == '8') echo(' selected="selected"'); ?>><?php echo(_('Aug')); ?></option>
        <option value="9"<?php if(@$_POST['m'] == '9') echo(' selected="selected"'); ?>><?php echo(_('Sep')); ?></option>
        <option value="10"<?php if(@$_POST['m'] == '10') echo(' selected="selected"'); ?>><?php echo(_('Oct')); ?></option>
        <option value="11"<?php if(@$_POST['m'] == '11') echo(' selected="selected"'); ?>><?php echo(_('Nov')); ?></option>
        <option value="12"<?php if(@$_POST['m'] == '12') echo(' selected="selected"'); ?>><?php echo(_('Dec')); ?></option>
    </select>
    
	<?php
	/*
    if(count($GLOBALS['_config']['email']) < 2)
    	echo('<span style="display:none">');
    ?></td><td>
    <select name="department">
        <option value=""<?php if(!$_POST['department']) echo(' selected="selected"'); ?>><?php echo(_('All')); ?></option><?php
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
        <option value=""<?php if(!$_POST['clerk']) echo(' selected="selected"'); ?>><?php echo(_('All')); ?></option><?php
        foreach($users as $user) {
			//warning if a user name is a it could colide with all
	        ?><option<?php if($_POST['clerk'] == $user['fullname']) echo(' selected="selected"'); ?>><?php echo($user['fullname']); ?></option><?php
		}
    ?></select><?php
    if(count($users) < 2)
    	echo('</span>');
	
    ?></td><td>
    <select name="status">
        <option value=""<?php if(!$_POST['status']) echo(' selected="selected"'); ?>><?php echo(_('All')); ?></option>
        <option value="activ"<?php if($_POST['status'] == 'activ') echo(' selected="selected"'); ?>><?php echo(_('Current')); ?></option>
        <option value="inactiv"<?php if($_POST['status'] == 'inactiv') echo(' selected="selected"'); ?>><?php echo(_('Completed')); ?></option>
        <option value="new"<?php if($_POST['status'] == 'new') echo(' selected="selected"'); ?>><?php echo(_('New')); ?></option>
        <option value="locked"<?php if($_POST['status'] == 'locked') echo(' selected="selected"'); ?>><?php echo(_('Locked')); ?></option>
        <option value="pbsok"<?php if($_POST['status'] == 'pbsok') echo(' selected="selected"'); ?>><?php echo(_('Ready')); ?></option>
        <option value="accepted"<?php if($_POST['status'] == 'accepted') echo(' selected="selected"'); ?>><?php echo(_('Expedited')); ?></option>
        <option value="giro"<?php if($_POST['status'] == 'giro') echo(' selected="selected"'); ?>><?php echo(_('Giro')); ?></option>
        <option value="cash"<?php if($_POST['status'] == 'cash') echo(' selected="selected"'); ?>><?php echo(_('Cash')); ?></option>
        <option value="pbserror"<?php if($_POST['status'] == 'pbserror') echo(' selected="selected"'); ?>><?php echo(_('Error')); ?></option>
        <option value="canceled"<?php if($_POST['status'] == 'canceled') echo(' selected="selected"'); ?>><?php echo(_('Canceled')); ?></option>
        <option value="rejected"<?php if($_POST['status'] == 'rejected') echo(' selected="selected"'); ?>><?php echo(_('Rejected')); ?></option>
    </select></td><td><input type="submit" value="Hent" /></td></tr></table>
    
</form>
<table style="width:100%; margin:0 0 113px 0">
    <thead>
        <tr>
            <td style="width:16px;"></td>
            <td><?php echo(_('ID')); ?></td>
            <td><?php echo(_('Created')); ?></td>
            <?php if(empty($_POST['clerk'])) { ?><td><?php echo(_('Responsible')); ?></td><?php } ?>
            <td><?php echo(_('Amount')); ?></td>
            <td><?php echo(_('Recipient')); ?></td>
        </tr>
    </thead>
    <tbody id="list"><?php
		foreach($fakturas as $i => $faktura) { ?><tr<?php
				if($i%2==0)
					echo(' class="altbc"'); ?>>
            <td style="text-align:center"><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php
				if($faktura['status'] == 'new')
					echo('<img src="/admin/images/table.png" alt="'._('New').'" title="'._('New').'" />');
				elseif($faktura['status'] == 'locked' && $faktura['sendt'])
					echo('<img src="/admin/images/email_go.png" alt="'._('Sent').'" title="'._('Sent to customer').'" />');
				elseif($faktura['status'] == 'locked')
					echo('<img src="/admin/images/lock.png" alt="'._('Locked').'" title="'._('Locked').'" />');
				elseif($faktura['status'] == 'pbsok')
					echo('<img src="/admin/images/money.png" alt="'._('Ready').'" title="'._('Ready').'" />');
				elseif($faktura['status'] == 'accepted')
					echo('<img src="/admin/images/creditcards.png" alt="'._('Expedited').'" title="'._('Expedited').'" />');
				elseif($faktura['status'] == 'giro')
					echo('<img src="/admin/images/building.png" alt="'._('Giro').'" title="'._('Giro').'" />');
				elseif($faktura['status'] == 'cash')
					echo('<img src="/admin/images/email.png" alt="'._('Cash').'" title="'._('Cash').'" />');
				elseif($faktura['status'] == 'pbserror')
					echo('<img src="/admin/images/error.png" alt="'._('Error').'" title="'._('Error').'" />');
				elseif($faktura['status'] == 'canceled')
					echo('<img src="/admin/images/bin.png" alt="'._('Canceled').'" title="'._('Canceled').'" />');
				elseif($faktura['status'] == 'rejected')
					echo('<img src="/admin/images/bin.png" alt="'._('Rejected').'" title="'._('Rejected').'" />');
					
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
$activityButtons[] = '<li><a href="faktura.php?function=new"><img src="images/table_add.png" width="16" height="16" alt="" title="'._('Create new').'" /> '._('Create new').'</a></li>';
$activityButtons[] = '<li><a href="fakturasearch.php"><img src="images/magnifier.png" width="16" height="16" alt="" title="'._('Advanced Search').'" /> '._('Search').'</a></li>';
if($_SESSION['_user']['access'] == 1) {
	$activityButtons[] = '<li><a href="fakturasvalidate.php"><img src="images/tick.png" width="16" height="16" alt="" title="'._('Validate').'" /> '._('Validate').'</a></li>';
}

require 'mainmenu.php';
?>
</body>
</html>
