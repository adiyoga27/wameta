<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\IncomingMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $devices = $user->isSuperAdmin() ? Device::all() : $user->devices;
        $deviceId = $request->get('device_id', $devices->first()?->id);

        $messages = IncomingMessage::where('device_id', $deviceId)
            ->latest()
            ->paginate(50);

        return view('messages.index', compact('messages', 'devices', 'deviceId'));
    }
}
