<?php

use AGCMS\Config;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\User;
use AGCMS\ORM;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';
require_once _ROOT_ . '/inc/countries.php';

$selected = [
    'id'         => (int) request()->get('id') ?: null,
    'year'       => (int) request()->get('y', date('Y')),
    'month'      => (int) request()->get('m'),
    'department' => request()->get('department'),
    'status'     => request()->get('status', 'activ'),
    'name'       => request()->get('name'),
    'tlf'        => request()->get('tlf'),
    'email'      => request()->get('email'),
    'momssats'   => request()->get('momssats'),
    'clerk'      => request()->get('clerk'),
];
if (null === $selected['clerk'] && !curentUser()->hasAccess(User::ADMINISTRATOR)) {
    $selected['clerk'] = curentUser()->getFullName();
}
if ('' === $selected['momssats']) {
    $selected['momssats'] = null;
}

$where = [];

if ($selected['month'] && $selected['year']) {
    $where[] = "`date` >= '" . $selected['year'] . '-' . $selected['month'] . "-01'";
    $where[] = "`date` <= '" . $selected['year'] . '-' . $selected['month'] . "-31'";
} elseif ($selected['year']) {
    $where[] = "`date` >= '" . $selected['year'] . "-01-01'";
    $where[] = "`date` <= '" . $selected['year'] . "-12-31'";
}

if ($selected['department']) {
    $where[] = '`department` = ' . db()->eandq($selected['department']);
}
if ($selected['clerk'] && !curentUser()->hasAccess(User::ADMINISTRATOR) || curentUser()->getFullName() == $selected['clerk']) {
    //Viewing your self
    $where[] = '(`clerk` = ' . db()->eandq($selected['clerk']) . " OR `clerk` = '')";
} elseif ($selected['clerk']) {
    //Viewing some one else
    $where[] = '`clerk` = ' . db()->eandq($selected['clerk']);
}

if ('activ' === $selected['status']) {
    $where[] = "(`status` = 'new' OR `status` = 'locked' OR `status` = 'pbsok' OR `status` = 'pbserror')";
} elseif ('inactiv' == $selected['status']) {
    $where[] = "(`status` != 'new' AND `status` != 'locked' AND `status` != 'pbsok' AND `status` != 'pbserror')";
} elseif ($selected['status']) {
    $where[] = '`status` = ' . db()->eandq($selected['status']);
}

if ($selected['name']) {
    $where[] = "`navn` LIKE '%" . db()->esc($selected['name']) . "%'";
}

if ($selected['tlf']) {
    $where[] = "(`tlf1` LIKE '%" . db()->esc($selected['tlf'])
        . "%' OR `tlf2` LIKE '%" . db()->esc($selected['tlf']) . "%')";
}

if ($selected['email']) {
    $where[] = "`email` LIKE '%" . db()->esc($selected['email']) . "%'";
}

if ($selected['momssats']) {
    $where[] = '`momssats` = ' . db()->eandq($selected['momssats']);
}

$where = implode(' AND ', $where);

if ($selected['id']) {
    $where = ' `id` = ' . $selected['id'];
}

$oldest = db()->fetchOne(
    "SELECT UNIX_TIMESTAMP(`date`) AS `date` FROM `fakturas` WHERE UNIX_TIMESTAMP(`date`) != '0' ORDER BY `date`"
);
$oldest = $oldest['date'] ?? time();
$oldest = date('Y', $oldest);

$data = [
    'title'         => _('Invoice list'),
    'currentUser'   => curentUser(),
    'selected'      => $selected,
    'countries'     => $countries,
    'departments'   => array_keys(Config::get('emails', [])),
    'users'         => ORM::getByQuery(User::class, 'SELECT * FROM `users` ORDER BY `fullname`'),
    'invoices'      => ORM::getByQuery(Invoice::class, 'SELECT * FROM `fakturas` WHERE ' . $where . ' ORDER BY `id` DESC'),
    'years'         => range($oldest, date('Y')),
    'statusOptions' => [
        ''         => 'All',
        'inactiv'  => _('Completed'),
        'new'      => _('New'),
        'locked'   => _('Locked'),
        'pbsok'    => _('Ready'),
        'accepted' => _('Expedited'),
        'giro'     => _('Giro'),
        'cash'     => _('Cash'),
        'pbserror' => _('Error'),
        'canceled' => _('Canceled'),
        'rejected' => _('Rejected'),
    ],
] + getBasicAdminTemplateData();

Render::output('admin-fakturas', $data);
