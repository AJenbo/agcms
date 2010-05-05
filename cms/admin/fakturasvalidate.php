<?php

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';

require_once '../inc/sajax.php';
require_once '../inc/config.php';
require_once '../inc/mysqli.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$sajax_request_type = 'POST';

$fakturas = $mysqli->fetch_array("SELECT `id`, `status`, `clerk`, `amount`, UNIX_TIMESTAMP(`paydate`) AS `paydate` FROM `fakturas` WHERE  `transferred` = 0 AND (`status` = 'accepted' OR `status` = 'cash' OR `status` = 'giro') ORDER BY `id` DESC");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo(_('Invoice validation')); ?></title>
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
<div id="canvas"><div id="headline"><?php echo(_('Invoice validation')); ?></div>
<table style="width:100%;">
    <thead>
        <tr>
            <td style="width:32px;"></td>
            <td><?php echo(_('ID')); ?></td>
            <td><?php echo(_('Pay date')); ?></td>
            <td><?php echo(_('Responsible')); ?></td>
            <td><?php echo(_('Amount')); ?></td>
        </tr>
    </thead>
    <tbody id="list"><?php
		foreach($fakturas as $i => $faktura) { ?><tr<?php
				if($i%2==0)
					echo(' class="altbc"'); ?>>
            <td style="text-align:center"><a style="display:inline;padding:0;" href="?id=<?php echo($faktura['id']); ?>"><img src="/admin/images/tick.png" alt="'._('Expedited').'" title="'._('Expedited').'" /></a><a style="display:inline;padding:0;" href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php
				if($faktura['status'] == 'accepted')
					echo('<img src="/admin/images/email.png" alt="'._('Expedited').'" title="'._('Expedited').'" />');
				elseif($faktura['status'] == 'giro')
					echo('<img src="/admin/images/email.png" alt="'._('Giro').'" title="'._('Giro').'" />');
				elseif($faktura['status'] == 'cash')
					echo('<img src="/admin/images/email.png" alt="'._('Cash').'" title="'._('Cash').'" />');
					
				//Efterkrav
				//Bank
				//Giro
			?></a></td>
            <td style="text-align:right"><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php echo($faktura['id']); ?></a></td>
            <td style="text-align:right"><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php echo(date('j/m/y', $faktura['paydate'])); ?></a></td>
            <td><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php echo($faktura['clerk']); ?></a></td>
            <td style="text-align:right"><a href="faktura.php?id=<?php echo($faktura['id']); ?>"><?php echo(number_format($faktura['amount'], 2, ',', '.')); ?></a></td>
        </tr><?php } ?>
    </tbody>
</table>
</div>
<?php
$activityButtons[] = '<li><a href="fakturasearch.php"><img src="images/magnifier.png" width="16" height="16" alt="" title="Advanceret '._('Search').'" /> '._('Search').'</a></li>';

require 'mainmenu.php';
?>
</body>
</html>
