<?php

use Sajax\Sajax;
use AGCMS\Render;
use AGCMS\ORM;
use AGCMS\Entity\User;

require_once __DIR__ . '/logon.php';

Sajax::export(['updateuser' => ['method' => 'POST']]);
Sajax::handleClientRequest();

$user = ORM::getOne(User::class, $_GET['id']);

$data = [
    'title' => _('Edit') . ' ' . $user->getFullName(),
    'userSession' => $_SESSION['_user'],
    'user' => $user,
    'accessLevels' => [
        0 => _('No access'),
        1 => _('Administrator'),
        3 => _('Non administrator'),
        4 => _('User'),
    ],
] + getBasicAdminTemplateData();

Render::output('admin-user', $data);
