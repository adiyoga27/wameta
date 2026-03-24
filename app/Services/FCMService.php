<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class FCMService
{
    protected $client;
    protected $projectId;

    public function __construct()
    {
        $this->client = new Client();
        $this->projectId = env('FIREBASE_PROJECT_ID');
    }

    /**
     * Send a notification to a specific FCM token.
     */
    public function sendNotification($token, $title, $body, $data = [])
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        
        // Note: For HTTP v1, you need an OAuth2 access token.
        // This requires the Google Admin SDK or manually generating the token using the Service Account JSON.
        // We will implement the token generation once the user provides the JSON key.
        
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map('strval', $data),
            ],
        ];

        try {
            // Placeholder for obtaining access token
            $accessToken = $this->getAccessToken();
            
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('FCM Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Placeholder for getting OAuth2 Access Token from Service Account JSON.
     * Requires "google/auth" package or manual implementation.
     */
    protected function getAccessToken()
    {
        return Cache::remember('fcm_access_token', 3500, function () {
            $keyPath = base_path(env('FIREBASE_SERVICE_ACCOUNT_JSON', 'storage/app/firebase-auth.json'));
            
            if (!file_exists($keyPath)) {
                Log::error('Firebase Service Account JSON not found at: ' . $keyPath);
                return null;
            }

            try {
                $credentials = new ServiceAccountCredentials(
                    'https://www.googleapis.com/auth/cloud-platform',
                    $keyPath
                );

                $token = $credentials->fetchAuthToken(HttpHandlerFactory::build($this->client));
                
                return $token['access_token'] ?? null;
            } catch (\Exception $e) {
                Log::error('FCM Token generation error: ' . $e->getMessage());
                return null;
            }
        });
    }
}
