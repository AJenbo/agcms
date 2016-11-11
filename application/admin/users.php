<?php
/**
 * List users
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/logon.php';

SAJAX::export(['deleteuser' => ['method' => 'POST']]);
SAJAX::handleClientRequest();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('Users and Groups'); ?></title>
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
<?php SAJAX::showJavascript(); ?>

function deleteuser(id, name)
{
    if (confirm('<?php echo sprintf(addcslashes(_('Do you realy want to delete the user \'%s\'?'), "\\'"), "'+name+'"); ?>') == true) {
        $('loading').style.visibility = 'hidden';
        x_deleteuser(id, deleteuser_r);
    }
}

function deleteuser_r(data) {
    if (data['error']) {
        alert(data['error']);
    }
    window.location.reload();
}

//-->
</script>
</head>
<body onload="$('loading').style.visibility = 'hidden';">
<div id="canvas"><div id="headline"><?php echo _('Users and Groups'); ?></div><table id="addressbook"><thead><tr><td></td><td><a href="?order=date"><a href="users.php"><?php echo _('Name'); ?></a></td><td><a href="?order=date"><?php echo _('Last online'); ?></a></td></tr></thead><tbody><?php

if (empty($_GET['order'])) {
    $users = db()->fetchArray("SELECT *, UNIX_TIMESTAMP(`lastlogin`) AS 'lastlogin' FROM `users` ORDER BY `fullname` ASC");
} else {
    $users = db()->fetchArray("SELECT *, UNIX_TIMESTAMP(`lastlogin`) AS 'lastlogin' FROM `users` ORDER BY `lastlogin` DESC");
}

foreach ($users as $key => $user) {
    echo '<tr';
    if ($key % 2) {
        echo ' class="altrow"';
    }
    echo '><td>';
    if ($_SESSION['_user']['access'] == 1) {
        echo ' <img src="images/cross.png" alt="X" title="' . _('Delete')
        . '" onclick="deleteuser(' . $user['id'] . ', \''
        . addcslashes($user['fullname'], "\\'") . '\')" />';
    }
    echo '</td><td><a href="user.php?id='.$user['id'].'">'.$user['fullname'].'</a></td><td><a href="user.php?id='.$user['id'].'">';
    if ($user['lastlogin'] == 0) {
        echo _('Never');
    } elseif ($user['lastlogin'] > time()-1800) {
        echo _('Online');
    } else {
        $dayes = round((time()-$user['lastlogin'])/86400);
        if ($dayes == 0) {
            $houres = round((time()-$user['lastlogin'])/3600);
            if ($houres == 0) {
                echo sprintf(_('%s minuts ago'), round((time()-$user['lastlogin'])/60));
            } else {
                echo sprintf(_('%s houres ago'), $houres);
            }
        } else {
            echo sprintf(_('%s dayes ago'), $dayes);
        }
    }
    echo '</a></td></tr>';
}

?></tbody></table></div><?php
$activityButtons[] = '<li><a href="/admin/newuser.php"><img src="images/table_add.png" width="16" height="16" alt="" title="'._('Create new').'" /> '._('Create new').'</a></li>';
require 'mainmenu.php';
?>
</body>
</html>