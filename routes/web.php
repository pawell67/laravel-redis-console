<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pawell67\RedisConsole\Http\Controllers\RedisConsoleController;

Route::get('/', [RedisConsoleController::class, 'index'])->name('redis-console.index');
Route::post('/execute', [RedisConsoleController::class, 'execute'])->name('redis-console.execute');
Route::get('/info', [RedisConsoleController::class, 'info'])->name('redis-console.info');
Route::get('/keys', [RedisConsoleController::class, 'keys'])->name('redis-console.keys');
Route::get('/count', [RedisConsoleController::class, 'count'])->name('redis-console.count');
Route::get('/inspect', [RedisConsoleController::class, 'inspect'])->name('redis-console.inspect');
