<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    /**
     * Outbound SMS notifications.
     *
     * Eavesdrop logs every matched SMS and can optionally intercept it before
     * it's delivered. Logging is on everywhere; intercepting is off on
     * production by default so real messages are never blocked on prod.
     */
    'sms' => [

        /**
         * Whether to log matched outbound SMS notifications.
         */
        'log' => env('EAVESDROP_SMS_LOG', true),

        /**
         * Whether to intercept (halt) matched SMS so they are never delivered.
         */
        'intercept' => env('EAVESDROP_SMS_INTERCEPT', env('APP_ENV', 'production') !== 'production'),

        /**
         * The notification channels to watch.
         *
         * List the channels your notifications send SMS through. Each entry may
         * be the channel's short driver name (e.g. 'vonage', 'twilio') or its
         * channel class (e.g. NotificationChannels\Twilio\TwilioChannel::class).
         * Matching is namespace- and case-insensitive.
         */
        'channels' => [
            'twilio',
        ],
    ],

    /**
     * Outbound email.
     *
     * Eavesdrop logs every email sent through Laravel's Mail layer (including
     * mail-channel notifications) and can optionally intercept it before it's
     * delivered.
     */
    'email' => [

        /**
         * Whether to log outbound email.
         */
        'log' => env('EAVESDROP_EMAIL_LOG', true),

        /**
         * Whether to intercept (halt) email so it is never delivered.
         */
        'intercept' => env('EAVESDROP_EMAIL_INTERCEPT', env('APP_ENV', 'production') !== 'production'),
    ],

    /**
     * The endpoint to view the inbox.
     */
    'endpoint' => env('EAVESDROP_ENDPOINT', 'eavesdrop'),

    /**
     * Middleware classes to protect the inbox.
     *
     * Eavesdrop's Authorize middleware is always appended after these. It
     * allows access in the local environment only, unless the application
     * defines a `viewEavesdrop` gate — in which case the gate decides.
     */
    'middleware' => [
        'web',
    ],
];
