<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/functions.php';

//access
//0:ny, ingen ratigheder.
//1:supper admin.
//2:admin.
//3:klader.
//4:gaest, ikke gemme.

session_start();

if (empty($_SESSION['_user'])) {
    if (!empty($_POST['username'])) {
        $user = db()->fetchOne(
            "
            SELECT * FROM `users`
            WHERE `name` = '" . db()->real_escape_string($_POST['username']) . "'"
        );
        if ($user && $user['access'] >= 1 && crypt($_POST['password'] ?? '', $user['password']) === $user['password']) {
            $_SESSION['_user'] = $user;
        }
        unset($_POST);
    }

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

db()->query("UPDATE `users` SET `lastlogin` =  NOW() WHERE `id` = " . $_SESSION['_user']['id']);
