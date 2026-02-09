<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Mailer Connection
    |--------------------------------------------------------------------------
    */
    'default' => env('MAIL_DRIVER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer from config
    |--------------------------------------------------------------------------
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@ecocycle.com'),
        'name' => env('MAIL_FROM_NAME', 'EcoCycle'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMTP Settings
    |--------------------------------------------------------------------------
    */
    'smtp' => [
        'host' => env('SMTP_HOST', 'smtp.gmail.com'),
        'port' => (int) (env('SMTP_PORT', 587)),
        'username' => env('SMTP_USER', null),
        'password' => env('SMTP_PASS', null),
        // 'tls' uses STARTTLS on port 587; 'ssl' uses direct SSL on port 465
        'encryption' => env('SMTP_ENCRYPTION', 'tls'),
        'timeout' => (int) (env('SMTP_TIMEOUT', 30)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log path
    |--------------------------------------------------------------------------
    */
    'log' => [
        'path' => env('MAIL_LOG_PATH', __DIR__ . '/../storage/logs/mail.log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Template Path
    |--------------------------------------------------------------------------
    */
    'template' => [
        'path' => base_path('resources/emails'),
    ],
];
