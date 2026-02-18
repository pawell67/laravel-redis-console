<?php

declare(strict_types=1);

namespace Pawell67\RedisConsole;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RedisConsoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'redis-console');

        $this->publishes([
            __DIR__ . '/../config/redis-console.php' => config_path('redis-console.php'),
        ], 'redis-console-config');

        $this->registerRoutes();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/redis-console.php', 'redis-console');
    }

    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('redis-console.path', 'redis-console'),
            'middleware' => config('redis-console.middleware', ['web']),
        ];
    }
}
