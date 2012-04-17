<?php
//access
//0:ny, ingen ratigheder.
//1:supper admin.
//2:admin.
//3:klader.
//4:gaest, ikke gemme.

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain('agcms', $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset('agcms', 'UTF-8');
textdomain('agcms');

require_once $_SERVER['DOCUMENT_ROOT'].'/inc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/mysqli.php';

session_start();

$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

if (empty($_SESSION['_user']) && !empty($_POST['username'])) {

    $_SESSION['_user'] = $mysqli->fetchArray("SELECT * FROM `users` WHERE `name` = '".addcslashes($_POST['username'], "'\\")."' LIMIT 1");
    $_SESSION['_user'] = @$_SESSION['_user'][0];
    if ($_SESSION['_user']['access'] < 1 || @$_SESSION['_user']['password'] != crypt(@$_POST['password'], $_SESSION['_user']['password']))
        unset($_SESSION['_user']);

    unset($_POST);
}

if (empty($_SESSION['_user'])) {
    sleep(1);
    header('HTTP/1.0 401 Unauthorized');
    ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo _('Login'); ?></title>
    <link type="text/css" rel="stylesheet" href="style/style.css">
    </head>
    <body style="margin:20px;" onload="document.getElementById('form').style.width = document.getElementById('width').offsetWidth+'px';">
    <form id="form" action="" method="post" style="margin: auto; text-align: right; background-color: #DDDDDD; border: 1px solid #AAAAAA; padding: 10px;">
<span id="width"><?php echo _('User:'); ?>
     <input name="username" />
     <br />
     <?php echo _('Password:'); ?>
     <input type="password" name="password" style="margin-top: 5px;" />
     <br />
     <input type="submit" value="Log ind" style="margin-top: 5px;" /></span>
    </form>
<p style="text-align: center; margin-top: 20px;"><a href="#" onclick="alert('Ring til Ole og forklar din situation!');"><?php echo _('Lost password?'); ?></a>
 &nbsp;
<a href="/admin/newuser.php"><?php echo _('Create account'); ?></a></p>
    </body>
    </html><?php
    die();
}

$mysqli->query("UPDATE `users` SET `lastlogin` =  NOW() WHERE `id` = ".$_SESSION['_user']['id']." LIMIT 1");
