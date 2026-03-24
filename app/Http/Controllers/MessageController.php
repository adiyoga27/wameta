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

    protected function getConversations($deviceId, $search = null)
    {
        $query = ChatMessage::where('chat_messages.device_id', $deviceId);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('contact_number', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('message_body', 'like', "%{$search}%");
            });
        }

        $conversations = $query->select(
                'contact_number',
                DB::raw('MAX(contact_name) as contact_name'),
                DB::raw('MAX(id) as last_message_id'),
                DB::raw('MAX(wa_timestamp) as last_time'),
                DB::raw('COUNT(*) as message_count'),
                DB::raw("SUM(CASE WHEN direction = 'in' AND is_read = 0 THEN 1 ELSE 0 END) as unread_count")
            )
            ->groupBy('contact_number')
            ->orderByDesc('last_time')
            ->get();

        $lastMessageIds = $conversations->pluck('last_message_id')->filter();
        $lastMessages = ChatMessage::whereIn('id', $lastMessageIds)->get()->keyBy('id');
        
        $convLabels = \App\Models\ConversationLabel::with('chatLabel')
            ->where('device_id', $deviceId)
            ->get()
            ->groupBy('contact_number');

        foreach ($conversations as $conv) {
            $conv->last_message = $lastMessages[$conv->last_message_id] ?? null;
            $conv->labels = $convLabels->get($conv->contact_number, collect());
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
        $search = $request->get('search');
        
        $conversations = $this->getConversations($deviceId, $search);

        if ($request->ajax()) {
            return view('messages.partials.conversation_list', compact('conversations', 'deviceId', 'search'))->render();
        }

        return view('messages.index', compact('conversations', 'devices', 'deviceId', 'search'));
    }

    /**
     * Chat view for a specific contact
     */
    public function show(Request $request, $deviceId, $contactNumber)
    {
        $devices = $this->getDevices();
        $device = Device::findOrFail($deviceId);
        $search = $request->get('search');

        // Mark incoming unread messages as read
        ChatMessage::where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->where('direction', 'in')
            ->where('is_read', false)
            ->update(['is_read' => true, 'status' => 'read']);

        $messages = ChatMessage::with('chatLabel')
            ->where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->orderBy('wa_timestamp', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $contactName = $messages->last()?->contact_name ?? $contactNumber;
        $conversations = $this->getConversations($deviceId, $search);

        $templates = MessageTemplate::where('device_id', $deviceId)
            ->where('status', 'APPROVED')
            ->get();
            
        $chatLabels = \App\Models\ChatLabel::where('device_id', $deviceId)->get();
        $conversationLabels = \App\Models\ConversationLabel::with('chatLabel')
            ->where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->get();

        return view('messages.show', compact(
            'messages', 'conversations', 'devices', 'deviceId', 'contactNumber', 'contactName', 'device', 'templates', 'search', 'chatLabels', 'conversationLabels'
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
            'message' => 'nullable|string|max:4096',
            'media_file' => 'nullable|file|max:16384', // 16MB max limit for most WA media
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

        $result = null;
        $messageType = 'text';
        $mediaUrl = null;

        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $mimeType = $file->getMimeType();
            
            // Determine type
            if (str_starts_with($mimeType, 'image/')) {
                $messageType = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $messageType = 'video';
            } elseif (str_starts_with($mimeType, 'audio/')) {
                $messageType = 'audio';
            } else {
                $messageType = 'document';
            }

            // Save locally and get path
            $path = $file->store('wa_media', 'public');
            $fullPath = storage_path('app/public/' . $path);
            $mediaUrl = $path;

            // Upload to Meta Server First
            $uploadResult = $waService->uploadMedia($fullPath, $mimeType);
            
            if (!$uploadResult['success']) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Gagal mengunggah media ke WhatsApp: ' . ($uploadResult['error'] ?? 'Unknown Error'),
                    ]);
                }
                return redirect()->back()->with('error', 'Gagal mengunggah media: ' . ($uploadResult['error'] ?? 'Unknown error'));
            }

            $mediaId = $uploadResult['media_id'];
            
            // Send Media Message
            $result = $waService->sendMediaMessage(
                $request->contact_number,
                $messageType,
                $mediaId,
                $request->message // pass as caption
            );

        } else {
            if (empty($request->message)) {
                return response()->json(['success' => false, 'error' => 'Pesan atau Media harus diisi.']);
            }
            $result = $waService->sendTextMessage($request->contact_number, $request->message);
        }

        $chatMsg = ChatMessage::create([
            'device_id' => $device->id,
            'contact_number' => $request->contact_number,
            'direction' => 'out',
            'message_type' => $messageType,
            'message_body' => $request->message,
            'media_url' => $mediaUrl,
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

            $chatMsg->load('chatLabel');

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

        $chatMsg->load('chatLabel');

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
            'message' => $chatMessage->fresh()->load('chatLabel'),
            'error' => $result['error'] ?? null,
        ]);
    }

    /**
     * Poll for new messages (realtime via AJAX)
     */
    public function poll(Request $request, $deviceId, $contactNumber)
    {
        $afterId = $request->get('after_id', 0);

        $newMessages = ChatMessage::with('chatLabel')
            ->where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->get();

        // Mark incoming newly polled messages as read since they are being displayed in the active chat window
        $incomingIds = $newMessages->where('direction', 'in')->where('is_read', false)->pluck('id');
        if ($incomingIds->isNotEmpty()) {
            ChatMessage::whereIn('id', $incomingIds)->update(['is_read' => true, 'status' => 'read']);
        }

        return response()->json([
            'messages' => $newMessages,
            'count' => $newMessages->count(),
        ]);
    }

    /**
     * Update global label for a message
     */
    public function setLabel(Request $request, $id)
    {
        $message = ChatMessage::findOrFail($id);
        
        $request->validate([
            'chat_label_id' => 'nullable|exists:chat_labels,id',
        ]);

        $message->update([
            'chat_label_id' => $request->chat_label_id
        ]);
        
        $message->load('chatLabel');

        return response()->json([
            'success' => true, 
            'label' => $message->chatLabel ? [
                'id' => $message->chatLabel->id,
                'name' => $message->chatLabel->name,
                'color_hex' => $message->chatLabel->color_hex
            ] : null,
            'message_id' => $message->id
        ]);
    }

    /**
     * Update labels for a conversation (number)
     */
    public function updateConversationLabels(Request $request, $deviceId, $contactNumber)
    {
        $request->validate([
            'label_ids' => 'array',
            'label_ids.*' => 'exists:chat_labels,id'
        ]);

        \Illuminate\Support\Facades\DB::transaction(function() use ($deviceId, $contactNumber, $request) {
            \App\Models\ConversationLabel::where('device_id', $deviceId)
                ->where('contact_number', $contactNumber)
                ->delete();

            if ($request->has('label_ids') && is_array($request->label_ids)) {
                $inserts = [];
                $now = now();
                foreach($request->label_ids as $id) {
                    $inserts[] = [
                        'device_id' => $deviceId,
                        'contact_number' => $contactNumber,
                        'chat_label_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
                \App\Models\ConversationLabel::insert($inserts);
            }
        });

        $labels = \App\Models\ConversationLabel::with('chatLabel')
            ->where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->get();
            
        return response()->json([
            'success' => true,
            'labels' => $labels->map(fn($l) => ['id' => $l->chat_label_id, 'name' => $l->chatLabel->name, 'color' => $l->chatLabel->color_hex])
        ]);
    }

    /**
     * Get recent unread notifications
     */
    public function unreadNotifications(Request $request)
    {
        $user = auth()->user();
        $deviceIds = $user->isSuperAdmin() ? Device::pluck('id') : $user->devices->pluck('id');

        $notifications = ChatMessage::whereIn('device_id', $deviceIds)
            ->where('direction', 'in')
            ->where('is_read', false)
            ->with('device')
            ->orderByDesc('wa_timestamp')
            ->limit(10)
            ->get()
            ->map(function($msg) {
                return [
                    'id' => $msg->id,
                    'contact_number' => $msg->contact_number,
                    'contact_name' => $msg->contact_name ?? $msg->contact_number,
                    'message_body' => \Illuminate\Support\Str::limit($msg->message_body, 100),
                    'time' => $msg->wa_timestamp ? $msg->wa_timestamp->diffForHumans() : 'Baru saja',
                    'url' => route('messages.show', [$msg->device_id, $msg->contact_number]),
                    'device_name' => $msg->device->name
                ];
            });

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'total_unread' => ChatMessage::whereIn('device_id', $deviceIds)->where('direction', 'in')->where('is_read', false)->count()
        ]);
    }

    /**
     * Mark all unread messages as read
     */
    public function markAllRead(Request $request)
    {
        $user = auth()->user();
        $deviceIds = $user->isSuperAdmin() ? Device::pluck('id') : $user->devices->pluck('id');

        ChatMessage::whereIn('device_id', $deviceIds)
            ->where('direction', 'in')
            ->where('is_read', false)
            ->update(['is_read' => true, 'status' => 'read']);

        return response()->json(['success' => true]);
    }
}
