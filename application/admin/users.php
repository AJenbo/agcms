<?php

use Sajax\Sajax;
use AGCMS\Render;
use AGCMS\ORM;
use AGCMS\Entity\User;

require_once __DIR__ . '/logon.php';

Sajax::export(['deleteuser' => ['method' => 'POST']]);
Sajax::handleClientRequest();

$users = ORM::getByQuery(
    User::class,
    "SELECT * FROM `users` ORDER BY " . (empty($_GET['order']) ? 'fullname' : 'lastlogin')
);

$data = [
    'title' => _('Users and Groups'),
    'userSession' => $_SESSION['_user'],
    'users' => $users,
] + getBasicAdminTemplateData();

Render::output('admin-users', $data);
