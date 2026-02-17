<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Redis Explorer Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Redis Explorer will be accessible from.
    |
    */
    'path' => env('REDIS_EXPLORER_PATH', 'redis-explorer'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Redis Explorer route.
    | You may add your own middleware to this list, for example
    | to restrict access in production environments.
    |
    */
    'middleware' => explode(',', env('REDIS_EXPLORER_MIDDLEWARE', 'web')),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | The default Redis connection to use. Set to null to use the default
    | connection from your database.php config.
    |
    */
    'connection' => env('REDIS_EXPLORER_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Max Database Index
    |--------------------------------------------------------------------------
    |
    | The maximum Redis database index available in the DB selector.
    | Standard Redis supports 0-15 (16 databases).
    |
    */
    'max_db' => (int) env('REDIS_EXPLORER_MAX_DB', 15),

    /*
    |--------------------------------------------------------------------------
    | Dangerous Commands
    |--------------------------------------------------------------------------
    |
    | These commands will require confirmation before execution.
    |
    */
    'dangerous_commands' => [
        'FLUSHDB',
        'FLUSHALL',
        'DEL',
        'UNLINK',
        'SHUTDOWN',
        'DEBUG',
        'CONFIG',
        'SLAVEOF',
        'REPLICAOF',
        'CLUSTER',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocked Commands
    |--------------------------------------------------------------------------
    |
    | These commands are completely blocked from being executed.
    |
    */
    'blocked_commands' => [
        'SHUTDOWN',
        'DEBUG',
    ],

];
