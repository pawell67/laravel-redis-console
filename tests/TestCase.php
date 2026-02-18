<?php

declare(strict_types=1);

namespace Pawell67\RedisConsole\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Pawell67\RedisConsole\RedisConsoleServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RedisConsoleServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));

        $app['config']->set('database.redis.client', 'phpredis');
        $app['config']->set('database.redis.default', [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ]);

        $app['config']->set('redis-console.path', 'redis-console');
        $app['config']->set('redis-console.middleware', ['web']);
        $app['config']->set('redis-console.connection', 'default');
        $app['config']->set('redis-console.max_db', 15);
        $app['config']->set('redis-console.read_only', false);
        $app['config']->set('redis-console.blocked_commands', ['SHUTDOWN', 'DEBUG']);
        $app['config']->set('redis-console.dangerous_commands', [
            'FLUSHDB', 'FLUSHALL', 'DEL', 'UNLINK',
            'CONFIG', 'SLAVEOF', 'REPLICAOF', 'CLUSTER',
        ]);
    }
}
