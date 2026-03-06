<?php

use App\Http\Controllers\TopupController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Midtrans Payment Notification
Route::post('/midtrans/notification', [TopupController::class, 'notification']);
