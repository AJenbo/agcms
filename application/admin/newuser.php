<?php

use AGCMS\Render;

require_once __DIR__ . '/../inc/Bootstrap.php';

$message = '';
$request = request();
if ($request->isMethod('POST')) {
    $fullname = $request->get('fullname');
    $name = $request->get('name');
    $password = $request->get('password');
    $password2 = $request->get('password2');

    if (!$fullname || !$name || !$password) {
        $message = _('All fields must be filled.');
    } elseif ($password !== $password2) {
        $message = _('The passwords does not match.');
    } elseif (db()->fetchOne('SELECT id FROM users WHERE name = ' . db()->eandq($name))) {
        $message = _('Username already taken.');
    } else {
        db()->query(
            '
            INSERT INTO users
            SET name = ' . db()->eandq($name) . ',
                password = ' . db()->eandq(crypt($password)) . ',
                fullname = ' . db()->eandq($fullname) . '
            '
        );
        $message = _('Your account has been created. An administrator will evaluate it shortly.');
        $emailbody = Render::render('admin/email/newuser', ['fullname' => $fullname]);
        sendEmails(_('New user'), $emailbody);
    }
}

Render::output('admin-newuser', ['message' => $message]);
