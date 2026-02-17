<?php

namespace Pawell67\RedisExplorer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RedisExplorerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'redis-explorer');

        $this->publishes([
            __DIR__ . '/../config/redis-explorer.php' => config_path('redis-explorer.php'),
        ], 'redis-explorer-config');

        $this->registerRoutes();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/redis-explorer.php', 'redis-explorer');
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
            'prefix' => config('redis-explorer.path', 'redis-explorer'),
            'middleware' => config('redis-explorer.middleware', ['web']),
        ];
    }
}
