<?php
/**
 * Configuration file loaded for acceptance tests.
 */
return [
    /*
     * Debug Level:
     *
     * Production Mode:
     * false: No error messages, errors, or warnings shown.
     *
     * Development Mode:
     * true: Errors and warnings shown.
     */
    'debug' => true,

    'App' => [
        'inTest' => true,
    ],

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     * - emailSalt - A random string used to create
     *   email verification tokens, and password reset
     *   tokens.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', 'a-random-value-that-you-cannot-guess'),
        'emailSalt' => env('EMAIL_SALT', 'a-random-value-used-for-emails'),
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * See app.php for more configuration options.
     */
    'Datasources' => [
        'default' => [
            'url' => env('DATABASE_TEST_URL', null),
        ],
        'test' => [
            'url' => env('DATABASE_TEST_URL', null),
        ],
    ],

    /*
     * Email configuration.
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     * See app.php for more configuration options.
     */
    'EmailTransport' => [
        'default' => [
            'host' => 'localhost',
            'port' => 25,
            'username' => null,
            'password' => null,
            'client' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'DebugKit' => [
        'ignoreAuthorization' => true,
    ]
];
