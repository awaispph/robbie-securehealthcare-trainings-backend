<?php

return [
    'available' => [
        'en',    // English
        'ar',    // Arabic
        'fr',    // French
        'de',    // German
    ],

    'default' => env('APP_LOCALE', 'en'),

    'languages' => [
        'en' => [
            'name' => 'English',
            'flag' => 'gb'
        ],
        'ar' => [
            'name' => 'العربية',
            'flag' => 'sa'
        ],
        'fr' => [
            'name' => 'Français',
            'flag' => 'fr'
        ],
        'de' => [
            'name' => 'Deutsch',
            'flag' => 'de'
        ]
    ]
];
