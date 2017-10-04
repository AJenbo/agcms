<?php

use AGCMS\Entity\Invoice;
use AGCMS\Entity\User;
use AGCMS\ORM;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

if ($_SESSION['_user']['access'] == User::ADMINISTRATOR) {
    if (!empty($_GET['id'])) {
        ORM::getOne(Invoice::class, $_GET['id'])->setTransferred(true)->save();
    } elseif (!empty($_GET['undoid'])) {
        ORM::getOne(Invoice::class, $_GET['undoid'])->setTransferred(false)->save();
    }
}

$invoices = ORM::getByQuery(
    Invoice::class,
    "SELECT * FROM `fakturas` WHERE `transferred` = 0 AND `status` = 'accepted' ORDER BY `paydate` DESC, `id` DESC"
);

$data = [
    'title' => _('Invoice validation'),
    'invoices' => $invoices,
] + getBasicAdminTemplateData();

Render::output('admin-fakturasvalidate', $data);
