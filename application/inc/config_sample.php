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

$GLOBALS['_config'] = [
    'base_url' => 'http://www.example.com',
    'site_name' => 'My store',
    'address' => '',
    'postcode' => '',
    'city' => '',
    'phone' => '',
    'fax' => '',

    'emails' => [
        'mail@example.com' => [
            'address'  => 'mail@example.com',
            'password' => 'password',
            'sentBox'  => 'INBOX.Sent',
            'imapHost' => 'imap.example.dk',
            'imapPort' => 143,
            'smtpHost' => 'smtp.example.com',
            'smtpPort' => '25',
            'smtpAuth' => true,
        ],
    ],

    'interests' => [
        'Stuff',
    ],

    // Payment gateway
    'pbsid' => '',
    'pbspassword' => '',
    'pbsfix' => '',
    'pbswindow' => 0,
    'pbspwd' => '',
    'pbssalt' => '',

    // Database
    'mysql_server' => 'db',
    'mysql_user' => 'root',
    'mysql_password' => '',
    'mysql_database' => 'agcms',

    //Admin options

    // Site color settings
    'bgcolor' => "FFFFFF",
    'bgcolorR' => 255,
    'bgcolorG' => 255,
    'bgcolorB' => 255,

    // Images
    'thumb_width' => 150,
    'thumb_height' => 150,

    'text_width' => 700,

    'frontpage_width' => 700,
];