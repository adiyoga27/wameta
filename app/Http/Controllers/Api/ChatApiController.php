<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatApiController extends Controller
{
    /**
     * Authenticate user and return token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = auth()->user();
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Get user's devices
     */
    public function devices()
    {
        $user = auth()->user();
        $devices = $user->isSuperAdmin() ? Device::all() : $user->devices;
        return response()->json($devices);
    }

    /**
     * Get conversations for a device
     */
    public function conversations($deviceId)
    {
        $user = auth()->user();
        // Check access
        if (!$user->isSuperAdmin() && !$user->devices()->where('device_id', $deviceId)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversations = ChatMessage::where('device_id', $deviceId)
            ->select(
                'contact_number',
                DB::raw('MAX(contact_name) as contact_name'),
                DB::raw('MAX(wa_timestamp) as last_time'),
                DB::raw('COUNT(*) as total_messages'),
                DB::raw("SUM(CASE WHEN direction = 'in' AND is_read = 0 THEN 1 ELSE 0 END) as unread_count")
            )
            ->groupBy('contact_number')
            ->orderByDesc('last_time')
            ->get();

        // Get last message body for each
        foreach ($conversations as $conv) {
            $lastMsg = ChatMessage::where('device_id', $deviceId)
                ->where('contact_number', $conv->contact_number)
                ->orderByDesc('wa_timestamp')
                ->first();
            $conv->last_message = Str::limit($lastMsg->message_body ?? '', 50);
        }

        return response()->json($conversations);
    }

    /**
     * Get message history
     */
    public function messages($deviceId, $contactNumber)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->devices()->where('device_id', $deviceId)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = ChatMessage::where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->orderBy('wa_timestamp', 'asc')
            ->get();

        // Mark as read
        ChatMessage::where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->where('direction', 'in')
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json($messages);
    }

    /**
     * Send message
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'contact_number' => 'required',
            'message' => 'required',
        ]);

        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->devices()->where('device_id', $request->device_id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $device = Device::find($request->device_id);
        $waService = new \App\Services\WhatsAppService($device);
        
        $res = $waService->sendTextMessage($request->contact_number, $request->message);

        if (isset($res['status']) && $res['status'] === true) {
            // Success logic (same as web)
            $chatMessage = ChatMessage::create([
                'device_id' => $device->id,
                'wa_id' => $res['data']['id'] ?? 'M_'.time(),
                'contact_number' => $request->contact_number,
                'message_body' => $request->message,
                'direction' => 'out',
                'status' => 'sent',
                'wa_timestamp' => now(),
                'is_read' => 1
            ]);
            return response()->json(['success' => true, 'message' => $chatMessage]);
        }

        return response()->json(['success' => false, 'error' => $res['message'] ?? 'Failed to send'], 500);
    }

    /**
     * Get all available chat labels
     */
    public function labels()
    {
        $labels = \App\Models\ChatLabel::all();
        return response()->json($labels);
    }

    /**
     * Assign labels to a conversation
     */
    public function assignLabels(Request $request, $deviceId, $contactNumber)
    {
        $request->validate(['labels' => 'required|array']);
        
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->devices()->where('device_id', $deviceId)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        \App\Models\ConversationLabel::where('device_id', $deviceId)
            ->where('contact_number', $contactNumber)
            ->delete();

        foreach ($request->labels as $labelId) {
            \App\Models\ConversationLabel::create([
                'device_id' => $deviceId,
                'contact_number' => $contactNumber,
                'chat_label_id' => $labelId
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Register mobile FCM token
     */
    public function registerFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required']);
        
        $user = auth()->user();
        $user->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['success' => true]);
    }
}
