<?php

namespace App\Http\Controllers;

use App\Models\BroadcastContact;
use App\Models\Device;
use App\Models\IncomingMessage;
use App\Models\WebhookLog;
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
            // Log unknown payload
            WebhookLog::create([
                'event_type' => 'unknown',
                'payload' => $data ?? ['raw' => $payload],
                'processed' => false,
            ]);
            return response('OK', 200);
        }

        foreach ($data['entry'] as $entry) {
            if (!isset($entry['changes'])) continue;

            foreach ($entry['changes'] as $change) {
                $field = $change['field'] ?? 'unknown';
                $value = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                // Find device
                $device = null;
                if ($phoneNumberId) {
                    $device = Device::where('phone_number_id', $phoneNumberId)->first();
                }

                // Determine event types present in this change
                $eventTypes = [];
                if (isset($value['messages'])) $eventTypes[] = 'messages';
                if (isset($value['statuses'])) $eventTypes[] = 'statuses';
                if (isset($value['errors'])) $eventTypes[] = 'errors';
                if (empty($eventTypes)) $eventTypes[] = $field;

                // Log every webhook change to webhook_logs
                $webhookLog = WebhookLog::create([
                    'device_id' => $device?->id,
                    'event_type' => implode(',', $eventTypes),
                    'phone_number_id' => $phoneNumberId,
                    'payload' => $change,
                    'processed' => false,
                ]);

                try {
                    if ($field !== 'messages') {
                        // Non-message field (e.g., account_update, message_template_status_update)
                        $webhookLog->update(['processed' => true]);
                        continue;
                    }

                    if (!$device) {
                        $webhookLog->update(['error_message' => 'Device not found for phone_number_id: ' . $phoneNumberId]);
                        continue;
                    }

                    // Handle incoming messages
                    if (isset($value['messages'])) {
                        $this->processMessages($device, $value);
                    }

                    // Handle status updates (delivery receipts)
                    if (isset($value['statuses'])) {
                        $this->processStatuses($value);
                    }

                    // Handle errors
                    if (isset($value['errors'])) {
                        Log::error('Webhook error from Meta', ['errors' => $value['errors']]);
                    }

                    $webhookLog->update(['processed' => true]);

                } catch (\Throwable $e) {
                    Log::error('Webhook processing error', [
                        'error' => $e->getMessage(),
                        'webhook_log_id' => $webhookLog->id,
                    ]);
                    $webhookLog->update([
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Process incoming messages
     */
    private function processMessages(Device $device, array $value): void
    {
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
                    $mediaUrl = $message['sticker']['id'] ?? null;
                    break;
                case 'location':
                    $lat = $message['location']['latitude'] ?? '';
                    $lon = $message['location']['longitude'] ?? '';
                    $name = $message['location']['name'] ?? '';
                    $messageBody = $name ? "[Location: {$name} ({$lat}, {$lon})]" : "[Location: {$lat}, {$lon}]";
                    break;
                case 'contacts':
                    $contactNames = [];
                    if (isset($message['contacts'])) {
                        foreach ($message['contacts'] as $c) {
                            $contactNames[] = $c['name']['formatted_name'] ?? 'Unknown';
                        }
                    }
                    $messageBody = '[Contact: ' . implode(', ', $contactNames) . ']';
                    break;
                case 'reaction':
                    $emoji = $message['reaction']['emoji'] ?? '';
                    $reactedMsgId = $message['reaction']['message_id'] ?? '';
                    $messageBody = $emoji ? "[Reaction: {$emoji}]" : "[Reaction removed]";
                    $mediaUrl = $reactedMsgId;
                    break;
                case 'button':
                    $messageBody = $message['button']['text'] ?? '[Button Reply]';
                    break;
                case 'interactive':
                    $interactiveType = $message['interactive']['type'] ?? 'unknown';
                    if ($interactiveType === 'button_reply') {
                        $messageBody = $message['interactive']['button_reply']['title'] ?? '[Button Reply]';
                    } elseif ($interactiveType === 'list_reply') {
                        $messageBody = $message['interactive']['list_reply']['title'] ?? '[List Reply]';
                    } else {
                        $messageBody = "[Interactive: {$interactiveType}]";
                    }
                    break;
                case 'order':
                    $itemCount = count($message['order']['product_items'] ?? []);
                    $messageBody = "[Order: {$itemCount} item(s)]";
                    break;
                case 'referral':
                    $messageBody = '[Referral: ' . ($message['referral']['headline'] ?? 'Ad Click') . ']';
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

    /**
     * Process status updates (delivery receipts)
     */
    private function processStatuses(array $value): void
    {
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
