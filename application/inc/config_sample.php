<?php

use App\DTO\EmailConfig;

/*
 * Configuration of site.
 *
 * @license  MIT http://opensource.org/licenses/MIT
 */
return [
    'enviroment' => 'develop',
    'sentry'     => '',
    'timezone'   => 'Europe/Copenhagen',
    'locale'     => 'da_DK',
    'base_url'   => 'http://localhost',
    'site_name'  => 'My store',
    'address'    => '',
    'postcode'   => '',
    'city'       => '',
    'phone'      => '',
    'has_count'  => false,

    'emails' => [
        'mail@example.com' => new EmailConfig(
            'mail@example.com',
            'password',
            'INBOX.Sent',
            'imap.example.dk',
            143,
            'smtp.example.com',
            25,
            true,
        ),
    ],

    'interests' => [
        'Stuff',
    ],

    // Payment gateway
    'pbsid'       => '',
    'pbspassword' => '',
    'pbsfix'      => '',
    'pbswindow'   => 0,
    'pbspwd'      => '',
    'pbssalt'     => '',

    // Database
    'db_dns'         => '',
    'mysql_server'   => 'db',
    'mysql_user'     => 'root',
    'mysql_password' => '',
    'mysql_database' => 'agcms',

    //Admin options
    'theme'       => 'default',
    'blank_image' => '/theme/default/images/intet-foto.jpg',

    // Site color settings
    'bgcolor'  => 'FFFFFF',
    'bgcolorR' => 255,
    'bgcolorG' => 255,
    'bgcolorB' => 255,

    // Images
    'thumb_width'  => 150,
    'thumb_height' => 150,

    'text_width' => 700,

    'frontpage_width' => 700,
];
