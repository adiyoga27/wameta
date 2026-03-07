<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Device;
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

    /**
     * Conversations list
     */
    public function index(Request $request)
    {
        $devices = $this->getDevices();
        $deviceId = $request->get('device_id', $devices->first()?->id);

        // Get latest message per contact
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

        // Attach last message body to each conversation
        $lastMessageIds = $conversations->pluck('last_message_id')->filter();
        $lastMessages = ChatMessage::whereIn('id', $lastMessageIds)->get()->keyBy('id');
        foreach ($conversations as $conv) {
            $conv->last_message = $lastMessages[$conv->last_message_id] ?? null;
        }

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

        // Get contact name from most recent message
        $contactName = $messages->last()?->contact_name ?? $contactNumber;

        // Get conversations for sidebar
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

        return view('messages.show', compact(
            'messages', 'conversations', 'devices', 'deviceId', 'contactNumber', 'contactName', 'device'
        ));
    }

    /**
     * Send a message
     */
    public function send(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'contact_number' => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        $device = Device::findOrFail($request->device_id);
        $waService = new WhatsAppService($device);

        $result = $waService->sendTextMessage($request->contact_number, $request->message);

        // Save to chat_messages
        ChatMessage::create([
            'device_id' => $device->id,
            'contact_number' => $request->contact_number,
            'direction' => 'out',
            'message_type' => 'text',
            'message_body' => $request->message,
            'wa_message_id' => $result['message_id'] ?? null,
            'wa_timestamp' => now(),
            'status' => $result['success'] ? 'sent' : 'failed',
        ]);

        if ($result['success']) {
            return redirect()->route('messages.show', [
                'deviceId' => $device->id,
                'contactNumber' => $request->contact_number,
            ])->with('success', 'Pesan terkirim!');
        }

        return redirect()->back()->with('error', 'Gagal mengirim: ' . ($result['error'] ?? 'Unknown'));
    }
}
