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
use App\Http\Controllers\ChatLabelController;
use App\Http\Controllers\FCMController;
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
    Route::get('/templates/{id}', [TemplateController::class, 'show'])->name('templates.show');
    Route::get('/templates/{id}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{id}', [TemplateController::class, 'update'])->name('templates.update');
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

    // Chat Labels
    Route::resource('chat-labels', ChatLabelController::class)->except(['create', 'show', 'edit']);

    // Messages / Chat
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{deviceId}/{contactNumber}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');
    Route::post('/messages/send-template', [MessageController::class, 'sendTemplate'])->name('messages.sendTemplate');
    Route::post('/messages/{chatMessage}/retry', [MessageController::class, 'retry'])->name('messages.retry');
    Route::put('/messages/{chatMessage}/label', [MessageController::class, 'setLabel'])->name('messages.setLabel');
    Route::put('/messages/{deviceId}/{contactNumber}/labels', [MessageController::class, 'updateConversationLabels'])->name('messages.updateConversationLabels');
    Route::get('/messages/{deviceId}/{contactNumber}/poll', [MessageController::class, 'poll'])->name('messages.poll');
    Route::get('/messages/recent-notifications', [MessageController::class, 'recentNotifications'])->name('messages.recent-notifications');
    Route::post('/messages/mark-all-read', [MessageController::class, 'markAllRead'])->name('messages.mark-all-read');
    Route::post('/save-fcm-token', [FCMController::class, 'saveToken'])->name('save-fcm-token');
    Route::get('/test-notification', function() {
        if (!auth()->user()->fcm_token) {
            return "FCM Token not found for this user. Please enable notifications in your browser first.";
        }
        $fcm = new \App\Services\FCMService();
        $res = $fcm->sendNotification(
            auth()->user()->fcm_token, 
            "Test Notification", 
            "This is a test notification from Wameta!",
            ['url' => route('dashboard')]
        );
        return $res ? "Notification sent! " . json_encode($res) : "Failed to send notification. Check logs.";
    })->name('test-notification');
});
