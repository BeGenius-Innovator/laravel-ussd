<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default USSD Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default USSD driver that will be used to
    | parse incoming requests. You may implement your own driver
    | for each gateway (Orange, Moov, Africa's Talking, etc.).
    |
    | Supported drivers:
    |   "default"        — Generic driver (sessionId, phoneNumber, text)
    |   "africastalking" — Africa's Talking gateway
    |   "orange"         — Orange telecom gateway
    |   "moov"           — Moov/Africell gateway
    |   "infobip"        — Infobip gateway
    |   "twilio"         — Twilio USSD gateway
    |   "beem"           — Beem Africa gateway
    |   "advanta"        — Advanta Africa gateway
    |   "hubtel"         — Hubtel Ghana gateway
    |   "mtn"            — MTN gateway
    |   "vodacom"        — Vodacom/Vodafone gateway
    |   "airtel"         — Airtel Africa gateway
    |
    */

    'default_driver' => env('USSD_DRIVER', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Session Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the session storage driver for USSD sessions.
    | You may store sessions in the database, Redis, or any other
    | supported cache/store driver.
    |
    | Supported: "database", "redis", "file", "array"
    |
    */

    'session_driver' => env('USSD_SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that a USSD session
    | should be considered valid. After this period, the session
    | will be considered expired and the user will start over.
    |
    | USSD sessions typically expire after 60-120 seconds of inactivity.
    |
    */

    'session_lifetime' => env('USSD_SESSION_LIFETIME', 2),

    /*
    |--------------------------------------------------------------------------
    | Session Table
    |--------------------------------------------------------------------------
    |
    | This is the database table name used for storing USSD sessions
    | when using the "database" session driver.
    |
    */

    'session_table' => 'ussd_sessions',

    /*
    |--------------------------------------------------------------------------
    | Routes Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used for all USSD routes registered by
    | the package, including the callback endpoint and simulator.
    |
    */

    'routes_prefix' => env('USSD_ROUTES_PREFIX', 'ussd'),

    /*
    |--------------------------------------------------------------------------
    | Default Menu
    |--------------------------------------------------------------------------
    |
    | The name of the default menu to display when a user starts
    | a new USSD session. This menu is typically the welcome screen.
    |
    */

    'default_menu' => 'welcome',

    /*
    |--------------------------------------------------------------------------
    | Max Input Length
    |--------------------------------------------------------------------------
    |
    | Maximum number of characters allowed per USSD input.
    | Most gateways enforce a limit of 182 characters.
    |
    */

    'max_input_length' => 182,

    /*
    |--------------------------------------------------------------------------
    | Simulator Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the built-in USSD simulator for testing.
    | Should always be false in production.
    |
    */

    'simulator_enabled' => env('USSD_SIMULATOR_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure whether USSD requests and responses should be logged.
    |
    */

    'logging' => [
        'enabled' => env('USSD_LOGGING_ENABLED', true),
        'channel' => env('USSD_LOG_CHANNEL', 'stack'),
    ],

];
