<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'foto' => [
        'gis_base'        => env('GIS_FOTO_BASE_URL', 'http://192.168.0.22/GIS/foto'),
        'production_base' => env('PRODUCTION_FOTO_BASE_URL', 'http://192.168.0.22/production/public/foto'),
        'sample_base'     => env('SAMPLE_FOTO_BASE_URL', 'http://192.168.0.22/SOM/public/foto'),
    ],

];
