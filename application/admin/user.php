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
        User::NO_ACCESS => _('No access'),
        User::ADMINISTRATOR => _('Administrator'),
        User::MANAGER => _('Manager'),
        User::CLERK => _('Clerk'),
    ],
] + getBasicAdminTemplateData();

Render::output('admin-user', $data);
