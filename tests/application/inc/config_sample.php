<?php

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

    'db_dns' => 'sqlite::memory:',

    'emails' => [
        'mail@gmail.com' => [
            'address'  => 'mail@gmail.com',
            'password' => '',
            'sentBox'  => 'INBOX.Sent',
            'imapHost' => '',
            'imapPort' => 0,
            'smtpHost' => '',
            'smtpPort' => '',
            'smtpAuth' => false,
        ],
    ],

    'interests' => [
        'Stuff',
    ],

    //Admin options
    'theme' => 'default',

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
