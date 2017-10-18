<?php

use AGCMS\Entity\Invoice;
use AGCMS\Entity\User;
use AGCMS\ORM;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

if (curentUser()->hasAccess(User::ADMINISTRATOR)) {
    if (request()->get('id')) {
        $invoice = ORM::getOne(Invoice::class, request()->get('id'));
        assert($invoice instanceof Invoice);
        $invoice->setTransferred(true)->save();
    } elseif (request()->get('undoid')) {
        $invoice = ORM::getOne(Invoice::class, request()->get('undoid'));
        assert($invoice instanceof Invoice);
        $invoice->setTransferred(false)->save();
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
