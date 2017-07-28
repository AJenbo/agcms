<?php

use AGCMS\Render;

/**
 * Handle request for the site and decide on how to generate the page.
 */
require_once __DIR__ . '/inc/Bootstrap.php';

session_start();
Render::sendCacheHeader();
Render::outputPage();
