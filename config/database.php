<?php

/**
 * Database Configuration
 *
 * Usage: $config = require config_path('database.php');
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "mysql", "sqlite"
    |
    */
    'driver' => env('DB_DRIVER', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [

        'mysql' => [
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '3306'),
            'database' => env('DB_NAME', ''),
            'username' => env('DB_USER', 'root'),
            'password' => env('DB_PASS', ''),
            'charset'  => env('DB_CHARSET', 'utf8mb4'),
        ],

        'sqlite' => [
            'path' => env('DB_PATH', 'storage/database.sqlite'),
        ],

    ],

];
