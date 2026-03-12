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
     * Upload media file to Meta Resumable Upload API and get a handle for template headers.
     * Docs: https://developers.facebook.com/docs/graph-api/guides/upload
     *
     * @param string $filePath Absolute path to the file on disk
     * @param string $fileName Original filename
     * @param int $fileLength File size in bytes
     * @param string $mimeType MIME type (image/jpeg, image/png, video/mp4, application/pdf)
     * @return array ['success' => bool, 'handle' => string|null, 'error' => string|null, 'full_response' => array|null]
     */
    public function uploadMediaForHandle(string $filePath, string $fileName, int $fileLength, string $mimeType): array
    {
        $appId = $this->device->app_id;
        if (empty($appId)) {
            return ['success' => false, 'error' => 'App ID tidak dikonfigurasi di device', 'handle' => null];
        }

        try {
            // Step 1: Create upload session
            $sessionResponse = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/{$appId}/uploads", [
                    'file_name' => $fileName,
                    'file_length' => $fileLength,
                    'file_type' => $mimeType,
                ]);

            $sessionResult = $sessionResponse->json();
            Log::info('WhatsApp Upload Session Response', ['response' => $sessionResult]);

            if (!$sessionResponse->successful() || empty($sessionResult['id'])) {
                return [
                    'success' => false,
                    'error' => $sessionResult['error']['message'] ?? 'Gagal membuat upload session',
                    'handle' => null,
                    'full_response' => $sessionResult,
                ];
            }

            $uploadSessionId = $sessionResult['id']; // e.g. "upload:xxxxx"

            // Step 2: Upload file binary
            $uploadResponse = Http::withHeaders([
                    'Authorization' => 'OAuth ' . $this->device->access_token,
                    'file_offset' => '0',
                ])
                ->withBody(file_get_contents($filePath), $mimeType)
                ->post("{$this->baseUrl}/{$uploadSessionId}");

            $uploadResult = $uploadResponse->json();
            Log::info('WhatsApp Upload File Response', ['response' => $uploadResult]);

            if (!$uploadResponse->successful() || empty($uploadResult['h'])) {
                return [
                    'success' => false,
                    'error' => $uploadResult['error']['message'] ?? 'Gagal upload file',
                    'handle' => null,
                    'full_response' => $uploadResult,
                ];
            }

            return [
                'success' => true,
                'handle' => $uploadResult['h'],
                'full_response' => $uploadResult,
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp uploadMediaForHandle error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'handle' => null];
        }
    }

    /**
     * Upload media file directly to Media API and get a media_id for sending messages.
     * Docs: https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media#upload-media
     *
     * @param string $filePath Absolute path to the file on disk
     * @param string $mimeType MIME type of the file
     * @return array ['success' => bool, 'media_id' => string|null, 'error' => string|null]
     */
    public function uploadMedia(string $filePath, string $mimeType): array
    {
        $phoneNumberId = $this->device->phone_number_id;
        if (empty($phoneNumberId)) {
            return ['success' => false, 'error' => 'Phone Number ID tidak dikonfigurasi di device', 'media_id' => null];
        }

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->device->access_token,
                ])
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post("{$this->baseUrl}/{$phoneNumberId}/media", [
                    'messaging_product' => 'whatsapp',
                    'type' => $mimeType,
                ]);

            $result = $response->json();
            Log::info('WhatsApp Upload Media Response', ['response' => $result]);

            if ($response->successful() && !empty($result['id'])) {
                return [
                    'success' => true,
                    'media_id' => $result['id'],
                ];
            }

            return [
                'success' => false,
                'error' => $result['error']['message'] ?? 'Gagal upload media',
                'media_id' => null,
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp uploadMedia error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'media_id' => null];
        }
    }

    /**
     * Download media from Meta API and save locally
     * Docs: https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media#download-media
     *
     * @param string $mediaId ID from webhook message payload
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public function downloadMedia(string $mediaId): array
    {
        try {
            // Step 1: Get download URL
            $urlResponse = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/{$mediaId}");

            $urlResult = $urlResponse->json();
            Log::info('WhatsApp Get Media URL', ['response' => $urlResult]);

            if (!$urlResponse->successful() || empty($urlResult['url'])) {
                return ['success' => false, 'error' => 'Gagal mendapatkan URL media', 'path' => null];
            }

            $downloadUrl = $urlResult['url'];
            $mimeType = $urlResult['mime_type'] ?? 'application/octet-stream';
            
            // Generate extension from mime type
            $extension = 'bin';
            $mimeMap = [
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                'video/mp4' => 'mp4', 'video/3gpp' => '3gpp',
                'audio/aac' => 'aac', 'audio/mp4' => 'm4a', 'audio/mpeg' => 'mp3', 'audio/amr' => 'amr', 'audio/ogg' => 'ogg',
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            ];
            if (isset($mimeMap[$mimeType])) {
                $extension = $mimeMap[$mimeType];
            } else if (preg_match('/^image\/(.+)$/', $mimeType, $m)) {
                $extension = $m[1];
            } else if (preg_match('/^video\/(.+)$/', $mimeType, $m)) {
                $extension = $m[1];
            } else if (preg_match('/^audio\/(.+)$/', $mimeType, $m)) {
                $extension = $m[1];
            }

            // Step 2: Download the actual file. Must include Bearer token.
            $fileResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->device->access_token,
            ])->get($downloadUrl);

            if (!$fileResponse->successful()) {
                return ['success' => false, 'error' => 'Gagal mengunduh file media', 'path' => null];
            }

            // Save locally
            $filename = $mediaId . '_' . time() . '.' . $extension;
            $path = 'wa_media/' . $filename;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $fileResponse->body());

            return ['success' => true, 'path' => $path];

        } catch (\Exception $e) {
            Log::error('WhatsApp downloadMedia error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'path' => null];
        }
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

        Log::info('WhatsApp createTemplate payload', ['payload' => $payload]);

        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/{$this->device->waba_id}/message_templates", $payload);

            $result = $response->json();
            Log::info('WhatsApp createTemplate response', ['status' => $response->status(), 'body' => $result]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $result];
            }

            return [
                'success' => false,
                'error' => $result['error']['message'] ?? 'Unknown error',
                'error_code' => $result['error']['code'] ?? 0,
                'error_detail' => $result['error']['error_user_msg'] ?? ($result['error']['error_data']['details'] ?? ''),
                'full_response' => $result, // Save full JSON for debugging
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
            } elseif (in_array($data['header_type'], ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                // For media headers, use the uploaded handle from Resumable Upload API
                if (!empty($data['header_handle'])) {
                    $header['example'] = [
                        'header_handle' => [$data['header_handle']],
                    ];
                }
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
                if (empty($btn['type']) || empty($btn['text'])) continue;

                if ($btn['type'] === 'URL') {
                    $buttons[] = ['type' => 'URL', 'text' => $btn['text'], 'url' => $btn['url']];
                } elseif ($btn['type'] === 'PHONE_NUMBER') {
                    $buttons[] = ['type' => 'PHONE_NUMBER', 'text' => $btn['text'], 'phone_number' => $btn['phone_number']];
                } elseif ($btn['type'] === 'QUICK_REPLY') {
                    $buttons[] = ['type' => 'QUICK_REPLY', 'text' => $btn['text']];
                } elseif ($btn['type'] === 'COPY_CODE') {
                    $buttons[] = ['type' => 'COPY_CODE', 'example' => $btn['copy_code'] ?? ''];
                } elseif ($btn['type'] === 'FLOW') {
                    $button = [
                        'type' => 'FLOW',
                        'text' => $btn['text'],
                        'flow_id' => $btn['flow_id'] ?? '',
                        'flow_action' => $btn['flow_action'] ?? 'navigate',
                    ];
                    $buttons[] = $button;
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
     * Send a free-form text message
     */
    public function sendTextMessage(string $to, string $text): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text],
        ];

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
     * Send a media message (image, video, document, audio)
     */
    public function sendMediaMessage(string $to, string $type, string $mediaId, ?string $caption = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $type, // 'image', 'video', 'document', 'audio'
            $type => [
                'id' => $mediaId
            ]
        ];

        if ($caption && in_array($type, ['image', 'video', 'document'])) {
            $payload[$type]['caption'] = $caption;
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
     * Send a template message to a phone number
     */
    public function sendTemplateMessage(string $to, string $templateName, string $language = 'id', array $parameters = [], array $headerData = []): array
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

        $components = [];

        // Add header if provided (e.g., media_id for IMAGE/VIDEO/DOCUMENT)
        if (!empty($headerData)) {
            $components[] = [
                'type' => 'header',
                'parameters' => [$headerData],
            ];
        }

        // Add body parameters if provided
        if (!empty($parameters)) {
            $bodyParams = [];
            foreach ($parameters as $param) {
                $bodyParams[] = ['type' => 'text', 'text' => $param];
            }
            if (!empty($bodyParams)) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => $bodyParams
                ];
            }
        }

        if (!empty($components)) {
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
