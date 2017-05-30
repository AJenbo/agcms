<?php

use AGCMS\Render;

require_once __DIR__ . '/../inc/Bootstrap.php';

$message = '';
if ($_POST) {
    if (empty($_POST['fullname']) || empty($_POST['name']) || empty($_POST['password'])) {
        $message = _('All fields must be filled.');
    } elseif ($_POST['password'] !== $_POST['password2']) {
        $message = _('The passwords does not match.');
    } elseif (db()->fetchArray('SELECT id FROM users WHERE name = \''.addcslashes($_POST['name'], "'").'\'')) {
        $message = _('Username already taken.');
    } else {
        db()->query(
            "
            INSERT INTO users
            SET name = '" . db()->esc($_POST['name']) . "',
                password = '" . db()->esc(@crypt($_POST['password'])) . "',
                fullname = '" . db()->esc($_POST['fullname']) . "'
            "
        );
        $message = _('Your account has been created. An administrator will evaluate it shortly.');
        $emailbody = Render::render('email-admin-newuser', ['fullname' => $_POST['fullname']]);
        sendEmails(_('New user'), $emailbody);
    }
}

Render::output('admin-newuser', compact('message'));
