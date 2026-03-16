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
use App\Http\Controllers\WebhookLogController;
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
        Route::get('/webhook-logs', [WebhookLogController::class, 'index'])->name('webhook-logs.index');
        Route::get('/webhook-logs/{webhookLog}', [WebhookLogController::class, 'show'])->name('webhook-logs.show');
        Route::post('/topups/manual', [TopupController::class, 'manualStore'])->name('topups.manual');
    });

    // Top Up (accessible by both superadmin and admin with device access)
    Route::get('/topups', [TopupController::class, 'index'])->name('topups.index');
    Route::post('/topups', [TopupController::class, 'store'])->name('topups.store');
    Route::get('/topups/finish', [TopupController::class, 'finish'])->name('topups.finish');
    Route::get('/topups/history/{date}', [TopupController::class, 'historyDetail'])->name('topups.history.detail');

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
    Route::post('/broadcasts/{broadcast}/add-contacts', [BroadcastController::class, 'addContacts'])->name('broadcasts.addContacts');
    Route::post('/broadcasts/contacts/{broadcastContact}/reset', [BroadcastController::class, 'resetContact'])->name('broadcasts.resetContact');
    Route::delete('/broadcasts/{broadcast}', [BroadcastController::class, 'destroy'])->name('broadcasts.destroy');

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    Route::get('/contacts/export-template', [ContactController::class, 'exportTemplate'])->name('contacts.exportTemplate');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::patch('/contacts/{contact}/category', [ContactController::class, 'updateContactCategory'])->name('contacts.updateCategory');

    // Contact Categories
    Route::post('/contact-categories', [ContactController::class, 'storeCategory'])->name('contact-categories.store');
    Route::put('/contact-categories/{category}', [ContactController::class, 'updateCategory'])->name('contact-categories.update');
    Route::delete('/contact-categories/{category}', [ContactController::class, 'destroyCategory'])->name('contact-categories.destroy');

    // Messages / Chat
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{deviceId}/{contactNumber}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');
    Route::post('/messages/send-template', [MessageController::class, 'sendTemplate'])->name('messages.sendTemplate');
    Route::post('/messages/{chatMessage}/retry', [MessageController::class, 'retry'])->name('messages.retry');
    Route::get('/messages/{deviceId}/{contactNumber}/poll', [MessageController::class, 'poll'])->name('messages.poll');
});
