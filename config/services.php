<?php

return [

    'infobip' => [
        'api_key'  => env('INFOBIP_API_KEY'),
        'base_url' => env('INFOBIP_BASE_URL'),
        'from'     => env('INFOBIP_FROM', 'POS-Ferretero'),
    ],

    // Pasarela de WhatsApp (OpenWA autoalojado). Ver docs de despliegue del bot.
    'openwa' => [
        'url'            => env('OPENWA_URL'),              // ej: https://wa.avanzas.digital
        'api_key'        => env('OPENWA_API_KEY'),
        'webhook_secret' => env('OPENWA_WEBHOOK_SECRET'),
        'permitir_trial' => env('OPENWA_PERMITIR_TRIAL', false),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
