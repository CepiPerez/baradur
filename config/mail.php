<?php

return [

    /*
    |---------------------------------------------------------------------------------------
    | Default Mailer
    |---------------------------------------------------------------------------------------
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |---------------------------------------------------------------------------------------
    | Mailer Configurations
    |---------------------------------------------------------------------------------------
    |
    | Supported: "smtp", "sendmail"
    |
    | NOTE: Most EMAIL Servers doesn't support TLS 1.0 anymore
    | If you're getting TLS security error try leaving MAIL_ENCRYPTION empty in .env file
    | (or just remove the line, then it will use empty encryption as default from this file)
    */

    'mailers' => [
        
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', ''),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -t -i'),
        ]

    ],

    /*
    |---------------------------------------------------------------------------------------
    | Global "From" Address
    |---------------------------------------------------------------------------------------
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'mperez.newrol@gmail.com'),
        'name' => env('MAIL_FROM_NAME', 'Matias Perez'),
    ]


];
