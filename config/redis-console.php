<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Redis Console Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Redis Console will be accessible from.
    |
    */
    'path' => env('REDIS_CONSOLE_PATH', 'redis-console'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Redis Console route.
    | You may add your own middleware to this list, for example
    | to restrict access in production environments.
    |
    */
    'middleware' => explode(',', env('REDIS_CONSOLE_MIDDLEWARE', 'web')),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | The default Redis connection to use. Set to null to use the default
    | connection from your database.php config.
    |
    */
    'connection' => env('REDIS_CONSOLE_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Max Database Index
    |--------------------------------------------------------------------------
    |
    | The maximum Redis database index available in the DB selector.
    | Standard Redis supports 0-15 (16 databases).
    |
    */
    'max_db' => (int) env('REDIS_CONSOLE_MAX_DB', 15),

    /*
    |--------------------------------------------------------------------------
    | Read-Only Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, all write/modify commands are blocked. Only read commands
    | like GET, MGET, KEYS, SCAN, TYPE, TTL, INFO, DBSIZE, etc. are allowed.
    |
    */
    'read_only' => (bool) env('REDIS_CONSOLE_READ_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Read-Only Allowed Commands
    |--------------------------------------------------------------------------
    |
    | Commands permitted in read-only mode. Any command not in this list
    | will be blocked when read_only is true.
    |
    */
    'read_only_commands' => [
        'GET', 'MGET', 'STRLEN', 'GETRANGE', 'SUBSTR',
        'KEYS', 'SCAN', 'EXISTS', 'TYPE', 'TTL', 'PTTL', 'OBJECT',
        'RANDOMKEY', 'DBSIZE', 'DUMP',
        'LLEN', 'LRANGE', 'LINDEX', 'LPOS',
        'SCARD', 'SMEMBERS', 'SISMEMBER', 'SMISMEMBER', 'SRANDMEMBER',
        'ZCARD', 'ZCOUNT', 'ZRANGE', 'ZRANGEBYSCORE', 'ZRANK', 'ZSCORE',
        'ZREVRANGE', 'ZREVRANGEBYSCORE', 'ZREVRANK', 'ZLEXCOUNT',
        'HGET', 'HMGET', 'HGETALL', 'HKEYS', 'HVALS', 'HLEN', 'HEXISTS', 'HSCAN',
        'XLEN', 'XRANGE', 'XREVRANGE', 'XINFO', 'XREAD',
        'PING', 'ECHO', 'INFO', 'CONFIG GET', 'SLOWLOG', 'TIME',
        'CLIENT LIST', 'CLIENT INFO', 'CLIENT GETNAME',
        'MEMORY USAGE', 'DEBUG OBJECT',
    ],

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
        'SELECT',
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
        'EVAL',
        'EVALSHA',
        'SCRIPT',
        'FUNCTION',
    ],

];
