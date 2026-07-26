<?php

use App\Http\Controllers\Api\BootstrapController;
use App\Http\Controllers\Api\DeviceAuthController;
use App\Http\Controllers\Api\PanicController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Middleware\ResolveFieldGuard;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [DeviceAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.auth.login');

    Route::middleware(['auth:sanctum', ResolveFieldGuard::class])->group(function () {
        Route::post('auth/logout', [DeviceAuthController::class, 'logout'])->name('api.auth.logout');

        Route::get('bootstrap', BootstrapController::class)->name('api.bootstrap');

        // Fora da fila de sincronização de propósito: pedido de socorro não
        // espera lote nem intervalo. Throttle generoso para não barrar quem
        // aperta o botão duas vezes por nervosismo.
        Route::post('panic', [PanicController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('api.panic');

        Route::post('sync/events', [SyncController::class, 'events'])->name('api.sync.events');
        Route::post('sync/attachments/{uuid}', [SyncController::class, 'attachment'])
            ->whereUuid('uuid')
            ->name('api.sync.attachment');
    });
});
