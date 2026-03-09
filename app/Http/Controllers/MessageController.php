<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Device;
use App\Models\MessageTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    protected function getDevices()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? Device::all() : $user->devices;
    }

    protected function getConversations($deviceId)
    {
        $conversations = ChatMessage::where('chat_messages.device_id', $deviceId)
            ->select(
                'contact_number',
                DB::raw('MAX(contact_name) as contact_name'),
                DB::raw('MAX(id) as last_message_id'),
                DB::raw('MAX(wa_timestamp) as last_time'),
                DB::raw('COUNT(*) as message_count'),
                DB::raw("SUM(CASE WHEN direction = 'in' AND status = 'received' THEN 1 ELSE 0 END) as unread_count")
            )
            ->groupBy('contact_number')
            ->orderByDesc('last_time')
            ->get();

        $lastMessageIds = $conversations->pluck('last_message_id')->filter();
        $lastMessages = ChatMessage::whereIn('id', $lastMessageIds)->get()->keyBy('id');
        foreach ($conversations as $conv) {
            $conv->last_message = $lastMessages[$conv->last_message_id] ?? null;
        }

        return $conversations;
    }

    /**
     * Conversations list
     */
    public function index(Request $request)
    {
        $devices = $this->getDevices();
        $deviceId = $request->get('device_id', $devices->first()?->id);
        $conversations = $this->getConversations($deviceId);

        return view('messages.index', compact('conversations', 'devices', 'deviceId'));
    }

    /**
     * Chat view for a specific contact
     */
    public function show(Request $request, $deviceId, $contactNumber)
    {
        $devices = $this->getDevices();
        $device = Device::findOrFail($deviceId);

        $messages = ChatMessage::where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->orderBy('wa_timestamp', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $contactName = $messages->last()?->contact_name ?? $contactNumber;
        $conversations = $this->getConversations($deviceId);

        $templates = MessageTemplate::where('device_id', $deviceId)
            ->where('status', 'APPROVED')
            ->get();

        return view('messages.show', compact(
            'messages', 'conversations', 'devices', 'deviceId', 'contactNumber', 'contactName', 'device', 'templates'
        ));
    }

    /**
     * Send a message (AJAX or form)
     */
    public function send(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'contact_number' => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        $device = Device::findOrFail($request->device_id);

        // Check if device has enough balance
        if ($device->balance <= 0) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Saldo perangkat tidak mencukupi (Rp 0 atau kurang).',
                ]);
            }
            return redirect()->back()->with('error', 'Gagal mengirim: Saldo perangkat tidak mencukupi.');
        }

        $waService = new WhatsAppService($device);

        $result = $waService->sendTextMessage($request->contact_number, $request->message);

        $chatMsg = ChatMessage::create([
            'device_id' => $device->id,
            'contact_number' => $request->contact_number,
            'direction' => 'out',
            'message_type' => 'text',
            'message_body' => $request->message,
            'wa_message_id' => $result['message_id'] ?? null,
            'wa_timestamp' => now(),
            'status' => $result['success'] ? 'sent' : 'failed',
        ]);

        // AJAX response
        if ($request->wantsJson()) {
            $is24hError = false;
            if (!$result['success'] && isset($result['error_code']) && $result['error_code'] == 131047) {
                $is24hError = true;
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $chatMsg,
                'error' => $result['error'] ?? null,
                'is_24h_window_error' => $is24hError,
            ]);
        }

        if ($result['success']) {
            return redirect()->route('messages.show', [
                'deviceId' => $device->id,
                'contactNumber' => $request->contact_number,
            ]);
        }

        return redirect()->back()->with('error', 'Gagal mengirim: ' . ($result['error'] ?? 'Unknown'));
    }

    /**
     * Send a template message (AJAX)
     */
    public function sendTemplate(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'contact_number' => 'required|string',
            'template_id' => 'required|exists:message_templates,id',
        ]);

        $device = Device::findOrFail($request->device_id);
        
        if ($device->balance <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Saldo perangkat tidak mencukupi (Rp 0 atau kurang).',
            ]);
        }
        
        $template = MessageTemplate::where('id', $request->template_id)
            ->where('device_id', $device->id)
            ->firstOrFail();

        $waService = new WhatsAppService($device);

        $headerData = [];
        if (in_array($template->header_type, ['IMAGE', 'VIDEO', 'DOCUMENT']) && $template->header_media_path) {
            $filePath = storage_path('app/public/' . $template->header_media_path);
            if (file_exists($filePath)) {
                $mimeType = mime_content_type($filePath);
                $uploadResult = $waService->uploadMedia($filePath, $mimeType);
                
                if ($uploadResult['success']) {
                    $mediaId = $uploadResult['media_id'];
                    $typeKey = strtolower($template->header_type);
                    $headerData = [
                        'type' => $typeKey,
                        $typeKey => ['id' => $mediaId]
                    ];
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Gagal memproses media template: ' . ($uploadResult['error'] ?? 'Unknown error'),
                    ]);
                }
            }
        }

        $result = $waService->sendTemplateMessage(
            $request->contact_number,
            $template->name,
            $template->language,
            [],
            $headerData
        );

        $chatMsg = ChatMessage::create([
            'device_id' => $device->id,
            'contact_number' => $request->contact_number,
            'direction' => 'out',
            'message_type' => 'template',
            'message_body' => "[Template: {$template->name}]\n" . $template->body,
            'wa_message_id' => $result['message_id'] ?? null,
            'wa_timestamp' => now(),
            'status' => $result['success'] ? 'sent' : 'failed',
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $chatMsg,
            'error' => $result['error'] ?? null,
        ]);
    }

    /**
     * Retry a failed message
     */
    public function retry(Request $request, ChatMessage $chatMessage)
    {
        if ($chatMessage->status !== 'failed' || $chatMessage->direction !== 'out') {
            return response()->json(['success' => false, 'error' => 'Pesan tidak bisa dikirim ulang']);
        }

        $device = Device::findOrFail($chatMessage->device_id);

        if ($device->balance <= 0) {
            return response()->json(['success' => false, 'error' => 'Saldo perangkat tidak mencukupi']);
        }

        $waService = new WhatsAppService($device);

        $result = $waService->sendTextMessage($chatMessage->contact_number, $chatMessage->message_body);

        $chatMessage->update([
            'wa_message_id' => $result['message_id'] ?? $chatMessage->wa_message_id,
            'status' => $result['success'] ? 'sent' : 'failed',
            'wa_timestamp' => now(),
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $chatMessage->fresh(),
            'error' => $result['error'] ?? null,
        ]);
    }

    /**
     * Poll for new messages (realtime via AJAX)
     */
    public function poll(Request $request, $deviceId, $contactNumber)
    {
        $afterId = $request->get('after_id', 0);

        $newMessages = ChatMessage::where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'messages' => $newMessages,
            'count' => $newMessages->count(),
        ]);
    }
}
