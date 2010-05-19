<?php
/*
ini_set('display_errors', 1);
error_reporting(-1);
/**/

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

function updateuser($id, $updates) {
	global $mysqli;
	
	if($_SESSION['_user']['access'] == 1 || $_SESSION['_user']['id'] == $id) {
		if($_SESSION['_user']['id'] == $id && $updates['access'] != $_SESSION['_user']['access']) {
			return array('error' => _('You can\'t change your own access level'));
		}
		$sql = "UPDATE `users` SET";
		foreach($updates as $key => $value)
			$sql .= " `".addcslashes($key, '`\\')."` = '".addcslashes($value, "'\\")."',";
		$sql = substr($sql, 0, -1);
		$sql .= ' WHERE `id` = '.$id;
		
		$mysqli->query($sql);
		return true;
	} else {
		return array('error' => _('You do not have the requred access level to change this user.'));
	}
}

$sajax_request_type = 'POST';

//$sajax_debug_mode = 1;
sajax_export(
	array('name' => 'updateuser', 'method' => 'POST')
);
//if this is a ajax call, this is where things end
sajax_handle_client_request();

$user = $mysqli->fetch_one("SELECT * FROM `users` WHERE id = ".$_GET['id']);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo(_('Edit').' '.$user['fullname']); ?></title>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript"><!--
id = <?php echo($_GET['id']); ?>;

<?php sajax_show_javascript(); ?>

function updateuser() {
	$('loading').style.visibility = '';
	var update = {};
	update.access = getSelectValue('access');
	x_updateuser(id, update, updateuser_r);
}

function updateuser_r(date) {
	if(date['error']) {
		alert(date['error']);
		window.location.reload();
	}
	$('loading').style.visibility = 'hidden';
}
//-->
</script>
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<link href="style/mainmenu.css" rel="stylesheet" type="text/css" />
<link href="style/style.css" rel="stylesheet" type="text/css" />
</head>
<body onload="$('loading').style.visibility = 'hidden';">
<div id="canvas"><div id="headline"><?php echo(_('Edit').' '.$user['fullname']); ?></div>
<select name="access" id="access"><?php

$accesslevels = array(
 0 => _('No access'),
 1 => _('Administrator'),
 3 => _('Non administrator'),
 4 => _('User')
);

foreach($accesslevels as $level => $name) {
		//warning if a user name is a it could colide with all
        ?><option<?php if($user['access'] == $level) echo(' selected="selected"'); ?> value="<?php echo($level); ?>"><?php echo($name); ?></option><?php
	}
?></select><?php
print_r($user);

$activityButtons[] = '<li><a onclick="updateuser(); return false;"><img src="images/disk.png" alt="" width="16" height="16" /> '._('Save').'</a></li>';

?></div><?php
require 'mainmenu.php';
?>
</body>
</html>
