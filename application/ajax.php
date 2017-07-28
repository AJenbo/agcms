<?php

use Sajax\Sajax;

/**
 * Handle AJAX requests.
 */
require_once __DIR__ . '/inc/Bootstrap.php';

Sajax::export([
    'getTable'   => ['method' => 'GET'],
    'getKat'     => ['method' => 'GET'],
    'getAddress' => ['method' => 'GET'],
]);
Sajax::handleClientRequest(false);
