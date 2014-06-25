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

defined('ENT_XML1')    or define('ENT_XML1',     16);
defined('ENT_XHTML')   or define('ENT_XHTML',    32);
defined('ENT_HTML401') or define('ENT_HTML401',   0);
defined('ENT_HTML5')   or define('ENT_HTML5', 16|32);

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
$GLOBALS['_config']['pbswindow'] = 0;
$GLOBALS['_config']['pbspwd'] = '';
$GLOBALS['_config']['pbssalt'] = '';

$GLOBALS['_config']['mysql_server'] = 'localhost';
$GLOBALS['_config']['mysql_user'] = '';
$GLOBALS['_config']['mysql_password'] = '';
$GLOBALS['_config']['mysql_database'] = 'mydb';

//Admin options

//FTP for makedir
$GLOBALS['_config']['ftp_User'] = '';
$GLOBALS['_config']['ftp_Pass'] = '';
$GLOBALS['_config']['ftp_Root'] = '';

//Site color settings
$GLOBALS['_config']['bgcolor'] = "FFFFFF";
$GLOBALS['_config']['bgcolorR'] = 255;
$GLOBALS['_config']['bgcolorG'] = 255;
$GLOBALS['_config']['bgcolorB'] = 255;

//Images
$GLOBALS['_config']['thumb_width'] = 150;
$GLOBALS['_config']['thumb_height'] = 150;

$GLOBALS['_config']['text_width'] = 700;

$GLOBALS['_config']['frontpage_width'] = 700;
