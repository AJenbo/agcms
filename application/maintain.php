<?php
/**
 * Functinos for manintaning and optimizing the database
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

//TODO move to /admin and require password to avoid DoS

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/functions.php';

SAJAX::export(
    [
        'optimizeTables'          => ['method' => 'POST', 'asynchronous' => false],
        'removeBadSubmisions'     => ['method' => 'POST', 'asynchronous' => false],
        'removeBadBindings'       => ['method' => 'POST', 'asynchronous' => false],
        'removeBadAccessories'    => ['method' => 'POST', 'asynchronous' => false],
        'removeNoneExistingFiles' => ['method' => 'POST', 'asynchronous' => false],
        'deleteTempfiles'         => ['method' => 'POST', 'asynchronous' => false],
        'sendDelayedEmail'        => ['method' => 'POST', 'asynchronous' => false],
    ]
);
SAJAX::handleClientRequest();
