<?php

use App\Http\Controllers\TopupController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Midtrans Payment Notification
Route::post('/midtrans/notification', [TopupController::class, 'notification']);

// Mobile API
use App\Http\Controllers\Api\ChatApiController;
use Illuminate\Http\Request;

Route::post('/login', [ChatApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::get('/devices', [ChatApiController::class, 'devices']);
    Route::get('/conversations/{deviceId}', [ChatApiController::class, 'conversations']);
    Route::get('/messages/{deviceId}/{contactNumber}', [ChatApiController::class, 'messages']);
    Route::post('/messages/send', [ChatApiController::class, 'sendMessage']);
    Route::get('/labels', [ChatApiController::class, 'labels']);
    Route::post('/conversations/{deviceId}/{contactNumber}/labels', [ChatApiController::class, 'assignLabels']);
    Route::post('/fcm-token', [ChatApiController::class, 'registerFcmToken']);
});
