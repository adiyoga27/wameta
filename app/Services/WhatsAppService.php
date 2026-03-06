<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl = 'https://graph.facebook.com/v21.0';
    protected Device $device;

    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->device->access_token,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Create a message template on Meta
     */
    public function createTemplate(array $data): array
    {
        $payload = [
            'name' => $data['name'],
            'language' => $data['language'] ?? 'id',
            'category' => $data['category'] ?? 'MARKETING',
            'components' => $this->buildComponents($data),
        ];

        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/{$this->device->waba_id}/message_templates", $payload);

            $result = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'data' => $result];
            }

            return [
                'success' => false,
                'error' => $result['error']['message'] ?? 'Unknown error',
                'error_code' => $result['error']['code'] ?? 0,
                'error_detail' => $result['error']['error_user_msg'] ?? ($result['error']['error_data']['details'] ?? ''),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp createTemplate error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build template components from form data
     */
    protected function buildComponents(array $data): array
    {
        $components = [];

        // Header
        if (isset($data['header_type']) && $data['header_type'] !== 'NONE') {
            $header = ['type' => 'HEADER', 'format' => $data['header_type']];
            if ($data['header_type'] === 'TEXT') {
                $header['text'] = $data['header_content'] ?? '';
            }
            $components[] = $header;
        }

        // Body
        $components[] = [
            'type' => 'BODY',
            'text' => $data['body'],
        ];

        // Footer
        if (!empty($data['footer'])) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $data['footer'],
            ];
        }

        // Buttons
        if (!empty($data['buttons'])) {
            $buttons = [];
            foreach ($data['buttons'] as $btn) {
                if ($btn['type'] === 'URL') {
                    $buttons[] = ['type' => 'URL', 'text' => $btn['text'], 'url' => $btn['url']];
                } elseif ($btn['type'] === 'PHONE_NUMBER') {
                    $buttons[] = ['type' => 'PHONE_NUMBER', 'text' => $btn['text'], 'phone_number' => $btn['phone_number']];
                } elseif ($btn['type'] === 'QUICK_REPLY') {
                    $buttons[] = ['type' => 'QUICK_REPLY', 'text' => $btn['text']];
                }
            }
            if (!empty($buttons)) {
                $components[] = ['type' => 'BUTTONS', 'buttons' => $buttons];
            }
        }

        return $components;
    }

    /**
     * Get all templates from Meta
     */
    public function getTemplates(): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/{$this->device->waba_id}/message_templates");

            $result = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'data' => $result['data'] ?? []];
            }

            return ['success' => false, 'error' => $result['error']['message'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a template from Meta
     */
    public function deleteTemplate(string $templateName): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->delete("{$this->baseUrl}/{$this->device->waba_id}/message_templates", [
                    'name' => $templateName,
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            $result = $response->json();
            return ['success' => false, 'error' => $result['error']['message'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a template message to a phone number
     */
    public function sendTemplateMessage(string $to, string $templateName, string $language = 'id', array $parameters = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        // Add parameters if provided
        if (!empty($parameters)) {
            $components = [];
            $bodyParams = [];
            foreach ($parameters as $param) {
                $bodyParams[] = ['type' => 'text', 'text' => $param];
            }
            if (!empty($bodyParams)) {
                $components[] = ['type' => 'body', 'parameters' => $bodyParams];
            }
            $payload['template']['components'] = $components;
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/{$this->device->phone_number_id}/messages", $payload);

            $result = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $result['messages'][0]['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $result['error']['message'] ?? 'Unknown error',
                'error_code' => $result['error']['code'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify webhook signature
     */
    public static function verifyWebhookSignature(string $payload, string $signature, string $appSecret): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get phone number details from Meta
     */
    public function getPhoneNumberInfo(): array
    {
        try {
            $fields = 'display_phone_number,verified_name,quality_rating,platform_type,throughput,code_verification_status,name_status,is_official_business_account,account_mode,is_pin_enabled,last_onboarded_time';
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/{$this->device->phone_number_id}", ['fields' => $fields]);

            $result = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'data' => $result];
            }

            return ['success' => false, 'error' => $result['error']['message'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get WhatsApp Business Account (WABA) details from Meta
     */
    public function getWABAInfo(): array
    {
        try {
            $fields = 'id,name,currency,timezone_id,message_template_namespace,account_review_status,business_verification_status,ownership_type,on_behalf_of_business_info,primary_funding_id,purchase_order_number';
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/{$this->device->waba_id}", ['fields' => $fields]);

            $result = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'data' => $result];
            }

            return ['success' => false, 'error' => $result['error']['message'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get WhatsApp Business Profile info
     */
    public function getBusinessProfile(): array
    {
        try {
            $fields = 'about,address,description,email,profile_picture_url,websites,vertical';
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/{$this->device->phone_number_id}/whatsapp_business_profile", ['fields' => $fields]);

            $result = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'data' => $result['data'][0] ?? $result];
            }

            return ['success' => false, 'error' => $result['error']['message'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get WABA analytics / conversation-based pricing usage
     */
    public function getAnalytics(): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/{$this->device->waba_id}", [
                    'fields' => 'analytics.start(0).end(' . time() . ').granularity(DAY)',
                ]);

            $result = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'data' => $result['analytics'] ?? $result];
            }

            return ['success' => false, 'error' => $result['error']['message'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
