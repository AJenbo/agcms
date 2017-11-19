<?php

use AGCMS\Entity\User;
use AGCMS\ORM;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

$users = ORM::getByQuery(
    User::class,
    'SELECT * FROM `users` ORDER BY ' . (request()->get('order') ? 'lastlogin' : 'fullname')
);

$data = [
    'title'       => _('Users and Groups'),
    'currentUser' => curentUser(),
    'users'       => $users,
] + getBasicAdminTemplateData();

Render::output('admin-users', $data);
