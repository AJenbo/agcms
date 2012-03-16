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

function deleteuser($id)
{
    if ($_SESSION['_user']['access'] == 1) {
        global $mysqli;
        $mysqli->query("DELETE FROM `users` WHERE `id` = ".$id);
    }
}

$sajax_request_type = 'POST';

//$sajax_debug_mode = 1;
sajax_export(
    array('name' => 'deleteuser', 'method' => 'POST')
);
//$sajax_remote_uri = '/ajax.php';
sajax_handle_client_request();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('Users and Groups') ?></title>
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
<link href="style/style.css" rel="stylesheet" type="text/css" />
<script type="text/javascript"><!--
<?php sajax_show_javascript(); ?>

function deleteuser(id, name)
{
    if (confirm('<?php echo sprintf(addcslashes(_('Do you realy want to delete the user \'%s\'?'), "\\'"), "'+name+'") ?>') == true) {
        $('loading').style.visibility = 'hidden';
        x_deleteuser(id, deleteuser_r);
    }
}

function deleteuser_r() {
    if (data['error'])
        alert(data['error']);
    window.location.reload();
}

//-->
</script>
</head>
<body onload="$('loading').style.visibility = 'hidden';">
<div id="canvas"><div id="headline"><?php echo _('Users and Groups') ?></div><table id="addressbook"><thead><tr><td></td><td><a href="?order=date"><a href="users.php"><?php echo _('Name') ?></a></td><td><a href="?order=date"><?php echo _('Last online') ?></a></td></tr></thead><tbody><?php

if (empty($_GET['order'])) {
    $users = $mysqli->fetch_array("SELECT *, UNIX_TIMESTAMP(`lastlogin`) AS 'lastlogin' FROM `users` ORDER BY `fullname` ASC");
} else {
    $users = $mysqli->fetch_array("SELECT *, UNIX_TIMESTAMP(`lastlogin`) AS 'lastlogin' FROM `users` ORDER BY `lastlogin` DESC");
}

foreach ($users as $key => $user) {
    echo '<tr'
    if ($key % 2) {
        echo ' class="altrow"'
    }
    echo '><td>'
    if ($_SESSION['_user']['access'] == 1) {
        echo ' <img src="images/cross.png" alt="X" title="'._('Delete').'" onclick="deleteuser('.$user['id'].', \''.addcslashes($user['fullname'], "\\'").'\'" />');
    }
    echo '</td><td><a href="user.php?id='.$user['id'].'">'.$user['fullname'].'</a></td><td><a href="user.php?id='.$user['id'].'">'
    if ($user['lastlogin'] == 0) {
        echo _('Never')
    } elseif ($user['lastlogin'] > time()-1800) {
        echo _('Online')
    } else {
        $dayes = round((time()-$user['lastlogin'])/86400);
        if ($dayes == 0) {
            $houres = round((time()-$user['lastlogin'])/3600);
            if ($houres == 0) {
                echo sprintf(_('%s minuts ago'), round((time()-$user['lastlogin'])/60))
            } else {
                echo sprintf(_('%s houres ago'), $houres)
            }
        } else {
            echo sprintf(_('%s dayes ago'), $dayes)
        }
    }
    echo '</a></td></tr>'
}

?></tbody></table></div><?php
require 'mainmenu.php';
?>
</body>
</html>
