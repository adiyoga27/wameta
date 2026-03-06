<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\Contact;
use App\Models\Device;
use App\Models\IncomingMessage;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $deviceIds = $user->isSuperAdmin()
            ? Device::pluck('id')
            : $user->devices()->pluck('devices.id');

        $stats = [
            'total_devices' => $user->isSuperAdmin() ? Device::count() : $user->devices()->count(),
            'total_templates' => MessageTemplate::whereIn('device_id', $deviceIds)->count(),
            'approved_templates' => MessageTemplate::whereIn('device_id', $deviceIds)->where('status', 'APPROVED')->count(),
            'total_broadcasts' => Broadcast::whereIn('device_id', $deviceIds)->count(),
            'total_contacts' => $user->isSuperAdmin() ? Contact::count() : Contact::where('user_id', $user->id)->count(),
            'total_messages' => IncomingMessage::whereIn('device_id', $deviceIds)->count(),
            'total_sent' => Broadcast::whereIn('device_id', $deviceIds)->sum('sent'),
            'total_delivered' => Broadcast::whereIn('device_id', $deviceIds)->sum('delivered'),
            'total_failed' => Broadcast::whereIn('device_id', $deviceIds)->sum('failed'),
        ];

        $recentBroadcasts = Broadcast::whereIn('device_id', $deviceIds)
            ->with(['device', 'messageTemplate'])
            ->latest()
            ->take(5)
            ->get();

        $recentMessages = IncomingMessage::whereIn('device_id', $deviceIds)
            ->with('device')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact('stats', 'recentBroadcasts', 'recentMessages'));
    }
}
