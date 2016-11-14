<?php
/**
 * Handle AJAX requests
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
