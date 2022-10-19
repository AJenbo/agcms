<?php

use App\DTO\EmailConfig;

/**
 * Configuration of site.
 *
 * @license  MIT http://opensource.org/licenses/MIT
 */
return [
    'enviroment' => 'test',
    'timezone'   => 'Europe/Copenhagen',
    'locale'     => 'en_US',
    'base_url'   => 'https://localhost',
    'site_name'  => 'My store',
    'address'    => '',
    'postcode'   => '',
    'city'       => '',
    'phone'      => '',
    'has_count'  => false,

    'emails' => [
        'mail@gmail.com' => new EmailConfig(
            'mail@gmail.com',
            '',
            'INBOX.Sent',
            '',
            0,
            '',
            0,
            false,
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
    'pbssalt'     => '',

    // Database
    'db_dns'         => 'sqlite::memory:',
    'mysql_user'     => '',
    'mysql_password' => '',

    //Admin options
    'theme'       => 'default',
    'blank_image' => '',

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
