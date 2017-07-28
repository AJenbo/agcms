<?php

use AGCMS\Entity\Invoice;
use AGCMS\ORM;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

if ($_SESSION['_user']['access'] == 1) {
    if (!empty($_GET['id'])) {
        db()->query("UPDATE `fakturas` SET `transferred` =  '1' WHERE `id` = " . (int) $_GET['id']);
    } elseif (!empty($_GET['undoid'])) {
        db()->query("UPDATE `fakturas` SET `transferred` =  '0' WHERE `id` = " . (int) $_GET['undoid']);
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
