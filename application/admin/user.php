<?php

use AGCMS\Render;
use AGCMS\ORM;
use AGCMS\Entity\User;

require_once __DIR__ . '/logon.php';

$user = ORM::getOne(User::class, request()->get('id'));
assert($user instanceof User);

$data = [
    'title'        => _('Edit') . ' ' . $user->getFullName(),
    'currentUser'  => curentUser(),
    'user'         => $user,
    'accessLevels' => [
        User::NO_ACCESS     => _('No access'),
        User::ADMINISTRATOR => _('Administrator'),
        User::MANAGER       => _('Manager'),
        User::CLERK         => _('Clerk'),
    ],
] + getBasicAdminTemplateData();

Render::output('admin-user', $data);
