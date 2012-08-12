<?php
/**
 * Configuration of site
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     http://www.arms-gallery.dk/
 */

$GLOBALS['_config']['base_url'] = 'http://www.example.com';
$GLOBALS['_config']['site_name'] = 'My store';
$GLOBALS['_config']['address'] = '';
$GLOBALS['_config']['postcode'] = '';
$GLOBALS['_config']['city'] = '';
$GLOBALS['_config']['phone'] = '';
$GLOBALS['_config']['fax'] = '';

$GLOBALS['_config']['email'][] = 'mail@example.com';
$GLOBALS['_config']['emailpasswords'][] = 'password';
$GLOBALS['_config']['emailsent'] = 'INBOX.Sent';
$GLOBALS['_config']['imap'] = 'imap.example.dk';
$GLOBALS['_config']['imapport'] = '143';

$GLOBALS['_config']['smtp'] = 'smtp.example.com';
$GLOBALS['_config']['smtpport'] = 25;
$GLOBALS['_config']['emailpassword'] = false;

$GLOBALS['_config']['interests'][] = 'Stuff';

$GLOBALS['_config']['pbsid'] = '';
$GLOBALS['_config']['pbspassword'] = '';
$GLOBALS['_config']['pbsfix'] = '';

$GLOBALS['_config']['mysql_server'] = 'localhost';
$GLOBALS['_config']['mysql_user'] = '';
$GLOBALS['_config']['mysql_password'] = '';
$GLOBALS['_config']['mysql_database'] = 'mydb';
