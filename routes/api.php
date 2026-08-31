<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $dbStatus = 'disconnected';
    $dbError = null;
    try {
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }

    return response()->json([
        'status' => 'online',
        'database' => $dbStatus,
        'database_error' => $dbError,
        'db_host' => config('database.connections.mysql.host'),
        'db_port' => config('database.connections.mysql.port'),
        'db_database' => config('database.connections.mysql.database'),
        'db_username' => config('database.connections.mysql.username'),
        'app_key_set' => ! empty(config('app.key')),
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug'),
        'session_driver' => config('session.driver'),
    ]);
});
