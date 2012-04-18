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
$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

function updateuser($id, $updates)
{
    global $mysqli;

    if ($_SESSION['_user']['access'] == 1 || $_SESSION['_user']['id'] == $id) {
        //Validate access lavel update
        if ($_SESSION['_user']['id'] == $id && $updates['access'] != $_SESSION['_user']['access']) {
            return array('error' => _('You can\'t change your own access level'));
        }

        //Validate password update
        if (!empty($updates['password_new'])) {
            if ($_SESSION['_user']['access'] == 1 && $_SESSION['_user']['id'] != $id) {
                $updates['password'] = crypt($updates['password_new']);
            } elseif ($_SESSION['_user']['id'] == $id) {
                $user = $mysqli->fetchOne("SELECT `password` FROM `users` WHERE id = ".$id);
                if (mb_substr($user['password'], 0, 13) == mb_substr(crypt($updates['password'], $user['password']), 0, 13)) {
                    $updates['password'] = crypt($updates['password_new']);
                } else {
                    return array('error' => _('Incorrect password.'));
                }
            } else {
                return array('error' => _('You do not have the requred access level to change the password for other users.'));
            }
        } else {
            unset($updates['password']);
        }
        unset($updates['password_new']);

        //Generate SQL command
        $sql = "UPDATE `users` SET";
        foreach ($updates as $key => $value) {
            $sql .= " `".addcslashes($key, '`\\')."` = '".addcslashes($value, "'\\")."',";
        }
        $sql = substr($sql, 0, -1);
        $sql .= ' WHERE `id` = '.$id;

        //Run SQL
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

$user = $mysqli->fetchOne("SELECT *, UNIX_TIMESTAMP(`lastlogin`) AS 'lastlogin' FROM `users` WHERE id = ".$_GET['id']);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php
echo _('Edit').' '.$user['fullname'];
?></title>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript"><!--
id = <?php echo $_GET['id'] ?>;

<?php sajax_show_javascript(); ?>

function updateuser() {
    if ($('password_new').value != $('password2').value) {
        alert('<?php echo addcslashes(_('The passwords doesn\'t match.'), "'\\"); ?>');
        return false;
    }
    $('loading').style.visibility = '';
    var update = {};
    update.access = getSelectValue('access');
    update.fullname = $('fullname').value;
    update.password = $('password').value;
    update.password_new = $('password_new').value;
    x_updateuser(id, update, updateuser_r);
}

function updateuser_r(date)
{
    if (date['error']) {
        alert(date['error']);
    }
    window.location.reload();
}
//-->
</script>
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<link href="style/mainmenu.css" rel="stylesheet" type="text/css" />
<link href="style/style.css" rel="stylesheet" type="text/css" />
</head>
<body onload="$('loading').style.visibility = 'hidden';">
<div id="canvas"><div id="headline"><?php
echo _('Edit').' '.$user['fullname'];
 ?></div>
<table><tbody>
<tr<?php
if ($_SESSION['_user']['access'] != 1
    && $_SESSION['_user']['id'] != $_GET['id']
) {
    echo ' style="display:none"';
}
?>><td><?php
echo _('Full name:');
?></td><td><input value="<?php
echo $user['fullname'];
?>" id="fullname" name="fullname" /></td></tr>
<tr<?php
if ($_SESSION['_user']['id'] == $_GET['id']
    || $_SESSION['_user']['access'] == 1
) {
    echo ' style="display:none"';
}
?>><td><?php echo _('Full name:'); ?></td><td><?php echo $user['fullname']; ?></td></tr>
<tr><td><?php echo _('User name:'); ?></td><td><?php echo $user['name']; ?></td></tr>
<tr><td><?php echo _('Last online:'); ?></td><td><?php echo date(_('d/m/Y H:i'), $user['lastlogin']); ?></td></tr>
<tr><td><?php echo _('Access level:'); ?></td><td><select name="access" id="access"><?php

$accesslevels = array(
 0 => _('No access'),
 1 => _('Administrator'),
 3 => _('Non administrator'),
 4 => _('User')
);

foreach ($accesslevels as $level => $name) {
    //warning if a user name is a it could colide with all
    ?><option<?php
    if ($user['access'] == $level) {
        echo ' selected="selected"';
    }
    ?> value="<?php echo $level ?>"><?php echo $name ?></option><?php
}
?></select></td></tr>
<tr<?php
if ($_SESSION['_user']['id'] != $_GET['id']) {
    echo ' style="display:none"';
}
?>><td><?php echo _('Password:'); ?></td><td><input type="password" id="password" name="password" /></td></tr>
<tr<?php
if ($_SESSION['_user']['access'] != 1 && $_SESSION['_user']['id'] != $_GET['id']) {
    echo ' style="display:none"';
}
?>><td><?php echo _('New password:'); ?></td><td><input type="password" id="password_new" name="password_new" /></td></tr>
<tr<?php
if ($_SESSION['_user']['access'] != 1 && $_SESSION['_user']['id'] != $_GET['id']) {
    echo ' style="display:none"';
}
?>><td><?php echo _('Repeat password:'); ?></td><td><input type="password" id="password2" name="password2" /></td></tr>

</tbody></table></div><?php

$activityButtons[] = '<li><a onclick="updateuser(); return false;"><img src="images/disk.png" alt="" width="16" height="16" /> '._('Save').'</a></li>';
require 'mainmenu.php';
?>
</body>
</html>
