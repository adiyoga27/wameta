<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookLogController extends Controller
{
    public function index(Request $request)
    {
        $devices = Device::orderBy('name')->get();
        $deviceId = $request->query('device_id');
        $eventType = $request->query('event_type');

        $query = WebhookLog::with('device')->orderBy('created_at', 'desc');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        if ($eventType) {
            $query->where('event_type', 'like', "%{$eventType}%");
        }

        $logs = $query->paginate(25);

        return view('webhook-logs.index', compact('logs', 'devices', 'deviceId', 'eventType'));
    }

    public function show(WebhookLog $webhookLog)
    {
        $webhookLog->load('device');
        return view('webhook-logs.show', compact('webhookLog'));
    }
}
