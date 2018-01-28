<?php

/**
 * Configuration of site.
 *
 * @license  MIT http://opensource.org/licenses/MIT
 */
return [
    'enviroment' => 'test',
    'timezone'   => 'Europe/Copenhagen',
    'locale'     => 'da_DK',
    'base_url'   => 'https://localhost',
    'site_name'  => 'My store',

    'db_dns'     => 'sqlite::memory:',

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
