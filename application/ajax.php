<?php
/**
 * Handle AJAX requests
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

Sajax\Sajax::export(
    [
        'getTable'   => ['method' => 'GET'],
        'getKat'     => ['method' => 'GET'],
        'getAddress' => ['method' => 'GET'],
    ]
);
Sajax\Sajax::handleClientRequest(false);
