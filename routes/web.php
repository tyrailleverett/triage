<?php

declare(strict_types=1);

use HotReloadStudios\Triage\Http\Controllers\DashboardController;
use HotReloadStudios\Triage\Http\Controllers\SettingsApiController;
use HotReloadStudios\Triage\Http\Controllers\TicketApiController;
use HotReloadStudios\Triage\Http\Controllers\TicketMessageApiController;
use HotReloadStudios\Triage\Http\Controllers\TicketNoteApiController;
use HotReloadStudios\Triage\Http\Middleware\AuthorizeTriage;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('triage.path'),
    'middleware' => array_merge((array) config('triage.middleware', ['web']), [AuthorizeTriage::class]),
    'as' => 'triage.',
], function (): void {
    // API routes
    Route::prefix('api')->group(function (): void {
        Route::get('tickets', [TicketApiController::class, 'index'])->name('api.tickets.index');
        Route::post('tickets', [TicketApiController::class, 'store'])->name('api.tickets.store');
        Route::get('tickets/{ticket}', [TicketApiController::class, 'show'])->name('api.tickets.show');
        Route::patch('tickets/{ticket}', [TicketApiController::class, 'update'])->name('api.tickets.update');
        Route::post('tickets/{ticket}/messages', [TicketMessageApiController::class, 'store'])->name('api.tickets.messages.store');
        Route::post('tickets/{ticket}/notes', [TicketNoteApiController::class, 'store'])->name('api.tickets.notes.store');
        Route::get('settings/notifications', [SettingsApiController::class, 'notifications'])->name('api.settings.notifications.show');
        Route::patch('settings/notifications', [SettingsApiController::class, 'updateNotifications'])->name('api.settings.notifications.update');
    });

    // Shell routes — must come after API routes to avoid catching api/* paths
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('{view}', DashboardController::class)
        ->where('view', '^(?!api/).*$')
        ->name('dashboard.catchall');
});
