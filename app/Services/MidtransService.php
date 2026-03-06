<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public static function getClientKey(): string
    {
        return config('services.midtrans.client_key', '');
    }

    public static function isProduction(): bool
    {
        return config('services.midtrans.is_production', false);
    }

    /**
     * Create Snap transaction token
     */
    public function createSnapToken(array $params): array
    {
        try {
            $snapToken = Snap::getSnapToken($params);
            return ['success' => true, 'snap_token' => $snapToken];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create Snap redirect URL
     */
    public function createSnapUrl(array $params): array
    {
        try {
            $snapUrl = Snap::createTransaction($params);
            return [
                'success' => true,
                'snap_token' => $snapUrl->token,
                'redirect_url' => $snapUrl->redirect_url,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle incoming notification
     */
    public function handleNotification(): Notification
    {
        return new Notification();
    }

    /**
     * Build transaction parameters
     */
    public function buildParams(string $orderId, float $amount, string $deviceName, string $userName, string $userEmail): array
    {
        return [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $amount,
            ],
            'customer_details' => [
                'first_name' => $userName,
                'email' => $userEmail,
            ],
            'item_details' => [
                [
                    'id' => 'TOPUP-' . $orderId,
                    'price' => (int) $amount,
                    'quantity' => 1,
                    'name' => 'Top Up Saldo: ' . substr($deviceName, 0, 40),
                ],
            ],
        ];
    }
}
