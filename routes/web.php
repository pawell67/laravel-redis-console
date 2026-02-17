<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pawell67\RedisExplorer\Http\Controllers\RedisExplorerController;

Route::get('/', [RedisExplorerController::class, 'index'])->name('redis-explorer.index');
Route::post('/execute', [RedisExplorerController::class, 'execute'])->name('redis-explorer.execute');
Route::get('/info', [RedisExplorerController::class, 'info'])->name('redis-explorer.info');
Route::get('/keys', [RedisExplorerController::class, 'keys'])->name('redis-explorer.keys');
