<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TopupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Superadmin only
    Route::middleware(\App\Http\Middleware\SuperAdminMiddleware::class)->group(function () {
        Route::resource('devices', DeviceController::class);
        Route::resource('users', UserController::class);
    });

    // Top Up (accessible by both superadmin and admin with device access)
    Route::get('/topups', [TopupController::class, 'index'])->name('topups.index');
    Route::post('/topups', [TopupController::class, 'store'])->name('topups.store');
    Route::get('/topups/finish', [TopupController::class, 'finish'])->name('topups.finish');

    // Templates
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::post('/templates/{id}/sync', [TemplateController::class, 'sync'])->name('templates.sync');
    Route::post('/templates/sync-all', [TemplateController::class, 'syncAll'])->name('templates.syncAll');
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy'])->name('templates.destroy');

    // Broadcasts
    Route::get('/broadcasts', [BroadcastController::class, 'index'])->name('broadcasts.index');
    Route::get('/broadcasts/create', [BroadcastController::class, 'create'])->name('broadcasts.create');
    Route::post('/broadcasts', [BroadcastController::class, 'store'])->name('broadcasts.store');
    Route::get('/broadcasts/{broadcast}', [BroadcastController::class, 'show'])->name('broadcasts.show');
    Route::post('/broadcasts/{broadcast}/send', [BroadcastController::class, 'send'])->name('broadcasts.send');

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    // Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
});
