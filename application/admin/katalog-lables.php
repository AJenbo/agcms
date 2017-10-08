<?php

use AGCMS\ORM;
use AGCMS\Entity\Contact;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

$date = request()->get('dato');
$contacts = [];
if ($date) {
    $contacts = ORM::getByQuery(
        Contact::class,
        "
        SELECT *
        FROM `email`
        WHERE `dato` > '" . db()->esc($date) . " 00:00:00'
          AND `navn` != ''
          AND `adresse` != ''
          AND `post` != ''
          AND `by` != ''
          AND `downloaded` = '0'
        ORDER BY dato
        "
    );
}

$data = [
    'date' => $date ?: date('Y-m-d', time() - 7 * 24 * 60 * 60),
    'contacts' => $contacts,
];
Render::output('admin-katalog-lables', $data);
