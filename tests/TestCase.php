<?php

declare(strict_types=1);

namespace Pawell67\RedisExplorer\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Pawell67\RedisExplorer\RedisExplorerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RedisExplorerServiceProvider::class,
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

        $app['config']->set('redis-explorer.path', 'redis-explorer');
        $app['config']->set('redis-explorer.middleware', ['web']);
        $app['config']->set('redis-explorer.connection', 'default');
        $app['config']->set('redis-explorer.max_db', 15);
        $app['config']->set('redis-explorer.blocked_commands', ['SHUTDOWN', 'DEBUG']);
        $app['config']->set('redis-explorer.dangerous_commands', [
            'FLUSHDB', 'FLUSHALL', 'DEL', 'UNLINK',
            'CONFIG', 'SLAVEOF', 'REPLICAOF', 'CLUSTER',
        ]);
    }
}
