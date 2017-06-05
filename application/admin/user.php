<?php

use Sajax\Sajax;

/**
 * Edit a user
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once __DIR__ . '/logon.php';

Sajax::export(['updateuser' => ['method' => 'POST']]);
Sajax::handleClientRequest();

$user = db()->fetchOne("SELECT *, UNIX_TIMESTAMP(`lastlogin`) AS 'lastlogin' FROM `users` WHERE id = " . $_GET['id']);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php
echo _('Edit').' '.$user['fullname'];
?></title>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.3.0/prototype.js"></script>
<script type="text/javascript"><!--
id = <?php echo $_GET['id'] ?>;

    <?php Sajax::showJavascript(); ?>

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

$accesslevels = [
    0 => _('No access'),
    1 => _('Administrator'),
    3 => _('Non administrator'),
    4 => _('User')
];

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
?>><td><?php
echo _('New password:');
?></td><td><input type="password" id="password_new" name="password_new" /></td></tr>
<tr<?php
if ($_SESSION['_user']['access'] != 1 && $_SESSION['_user']['id'] != $_GET['id']) {
    echo ' style="display:none"';
}
?>><td><?php
echo _('Repeat password:');
?></td><td><input type="password" id="password2" name="password2" /></td></tr></tbody></table></div><?php

$activityButtons[] = '<li><a onclick="updateuser(); return false;"><img src="images/disk.png" alt="" width="16" height="16" /> '
    ._('Save') . '</a></li>';
require 'mainmenu.php';
?></body></html>
