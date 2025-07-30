<?php

/**
 * Application Configuration
 * 
 * Core application settings and service providers.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'name' => env('APP_NAME', 'EcoCycle'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes.
    |
    */
    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */
    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions.
    |
    */
    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider.
    |
    */
    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available.
    |
    */
    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the encryption service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe.
    |
    */
    'key' => env('APP_KEY', ''),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log settings for your application.
    |
    */
    'log_channel' => env('LOG_CHANNEL', 'stack'),

    'log_level' => env('LOG_LEVEL', 'debug'),

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Mail settings for sending notifications and emails.
    |
    */
    'mail' => [
        'driver' => env('MAIL_MAILER', 'smtp'),
        'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
        'port' => env('MAIL_PORT', 2525),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@ecocycle.com'),
            'name' => env('MAIL_FROM_NAME', 'EcoCycle'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Services Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for various external services.
    |
    */
    'services' => [
        'notification' => [
            'enabled' => true,
            'driver' => env('NOTIFICATION_DRIVER', 'database'),
        ],
        'payment' => [
            'driver' => env('PAYMENT_DRIVER', 'stripe'),
            'stripe' => [
                'key' => env('STRIPE_KEY'),
                'secret' => env('STRIPE_SECRET'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application.
    |
    */
    'providers' => [
        // Core Service Providers
        'Core\Providers\RouteServiceProvider',
        'Core\Providers\DatabaseServiceProvider',
        'Core\Providers\SessionServiceProvider',
        'Core\Providers\ViewServiceProvider',
        'Core\Providers\EventServiceProvider',

        // Application Service Providers
        'Services\PaymentServiceProvider',
        'Services\NotificationServiceProvider',
        'Services\BiddingServiceProvider',
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started.
    |
    */
    'aliases' => [
        'App' => 'Core\Application',
        'Config' => 'Core\Config',
        'DB' => 'Core\Database',
        'Request' => 'Core\Http\Request',
        'Response' => 'Core\Http\Response',
        'Router' => 'Core\Router',
        'Session' => 'Core\Session\SessionManager',
        'Validator' => 'Core\Validator',
    ],
];