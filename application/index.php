<?php
/**
 * Handle request for the site and decide on how to generate the page
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

session_start();
Render::sendCacheHeader();
Render::outputPage();
