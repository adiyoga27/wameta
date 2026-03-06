<?php

namespace App\Http\Controllers;

use App\Models\BroadcastContact;
use App\Models\Device;
use App\Models\IncomingMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Verify webhook (GET request from Meta)
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe') {
            // Try to find a device with this verify token
            $device = Device::where('webhook_verify_token', $token)->first();

            if ($device) {
                Log::info('Webhook verified for device: ' . $device->name);
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }
        }

        Log::warning('Webhook verification failed', ['token' => $token]);
        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook events (POST request from Meta)
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true);

        Log::info('Webhook received', ['data' => $data]);

        if (!isset($data['entry'])) {
            return response('OK', 200);
        }

        foreach ($data['entry'] as $entry) {
            if (!isset($entry['changes'])) continue;

            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'messages') continue;

                $value = $change['value'];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                if (!$phoneNumberId) continue;

                $device = Device::where('phone_number_id', $phoneNumberId)->first();
                if (!$device) continue;

                // Handle incoming messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $contactName = null;
                        if (isset($value['contacts'])) {
                            foreach ($value['contacts'] as $contact) {
                                if ($contact['wa_id'] === $message['from']) {
                                    $contactName = $contact['profile']['name'] ?? null;
                                    break;
                                }
                            }
                        }

                        $messageBody = null;
                        $mediaUrl = null;
                        $messageType = $message['type'] ?? 'text';

                        switch ($messageType) {
                            case 'text':
                                $messageBody = $message['text']['body'] ?? null;
                                break;
                            case 'image':
                                $messageBody = $message['image']['caption'] ?? '[Image]';
                                $mediaUrl = $message['image']['id'] ?? null;
                                break;
                            case 'video':
                                $messageBody = $message['video']['caption'] ?? '[Video]';
                                $mediaUrl = $message['video']['id'] ?? null;
                                break;
                            case 'document':
                                $messageBody = $message['document']['filename'] ?? '[Document]';
                                $mediaUrl = $message['document']['id'] ?? null;
                                break;
                            case 'audio':
                                $messageBody = '[Audio]';
                                $mediaUrl = $message['audio']['id'] ?? null;
                                break;
                            case 'sticker':
                                $messageBody = '[Sticker]';
                                break;
                            case 'location':
                                $lat = $message['location']['latitude'] ?? '';
                                $lon = $message['location']['longitude'] ?? '';
                                $messageBody = "[Location: {$lat}, {$lon}]";
                                break;
                            default:
                                $messageBody = "[{$messageType}]";
                        }

                        IncomingMessage::create([
                            'device_id' => $device->id,
                            'from_number' => $message['from'],
                            'from_name' => $contactName,
                            'message_type' => $messageType,
                            'message_body' => $messageBody,
                            'media_url' => $mediaUrl,
                            'wa_message_id' => $message['id'] ?? null,
                            'wa_timestamp' => isset($message['timestamp'])
                                ? \Carbon\Carbon::createFromTimestamp($message['timestamp'])
                                : now(),
                        ]);
                    }
                }

                // Handle status updates (delivery receipts)
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $waMessageId = $status['id'] ?? null;
                        $messageStatus = $status['status'] ?? null;

                        if ($waMessageId && $messageStatus) {
                            $bc = BroadcastContact::where('wa_message_id', $waMessageId)->first();
                            if ($bc) {
                                $newStatus = match ($messageStatus) {
                                    'sent' => 'sent',
                                    'delivered' => 'delivered',
                                    'read' => 'read',
                                    'failed' => 'failed',
                                    default => $bc->status,
                                };

                                $bc->update([
                                    'status' => $newStatus,
                                    'error_message' => $status['errors'][0]['title'] ?? $bc->error_message,
                                ]);

                                // Update broadcast counts
                                $broadcast = $bc->broadcast;
                                if ($broadcast) {
                                    $broadcast->update([
                                        'sent' => $broadcast->broadcastContacts()->where('status', 'sent')->count(),
                                        'delivered' => $broadcast->broadcastContacts()->where('status', 'delivered')->count(),
                                        'read' => $broadcast->broadcastContacts()->where('status', 'read')->count(),
                                        'failed' => $broadcast->broadcastContacts()->where('status', 'failed')->count(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return response('OK', 200);
    }
}
