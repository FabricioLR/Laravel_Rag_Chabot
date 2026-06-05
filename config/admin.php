<?php



return [

    /*
    |--------------------------------------------------------------------------
    | Admin dashboard configuration
    |--------------------------------------------------------------------------
    */
    
    'dashboard' => [
        'admin_user' => env('ADMIN_NAME'),
        'admin_email' => env('ADMIN_EMAIL'),
        'admin_password' => env('ADMIN_PASSWORD'),
    ],

    'widget' => [
        'enabled' => (bool)env('ENABLE_LOCAL_WIDGET', false),
        'token' => env('LOCAL_WIDGET_TOKEN')
    ]

];
