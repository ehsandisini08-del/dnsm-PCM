<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DnsServerController;
use App\Http\Controllers\Api\V1\RecordController;
use App\Http\Controllers\Api\V1\ZoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DNS Manager REST API v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Authentication Endpoint
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    // Authenticated Endpoints (Sanctum Protected)
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // User Profile & Auth
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // DNS Zones CRUD & Operations
        Route::apiResource('zones', ZoneController::class);
        Route::post('/zones/{zone}/sync', [ZoneController::class, 'sync']);

        // Zone Records Nested Routes
        Route::get('/zones/{zone}/records', [RecordController::class, 'indexByZone']);
        Route::post('/zones/{zone}/records', [RecordController::class, 'storeForZone']);

        // Individual Records CRUD
        Route::get('/records/{record}', [RecordController::class, 'show']);
        Route::put('/records/{record}', [RecordController::class, 'update']);
        Route::delete('/records/{record}', [RecordController::class, 'destroy']);

        // DNS Servers
        Route::get('/dns-servers', [DnsServerController::class, 'index']);
        Route::get('/dns-servers/{dns_server}', [DnsServerController::class, 'show']);
        Route::post('/dns-servers/{dns_server}/health-check', [DnsServerController::class, 'healthCheck']);

        // Customers
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    });
});
