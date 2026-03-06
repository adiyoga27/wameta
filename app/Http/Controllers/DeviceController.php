<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\Device;
use App\Models\IncomingMessage;
use App\Models\MessageTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::with('users')->latest()->get();
        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        return view('devices.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'waba_id' => 'nullable|string',
            'phone_number_id' => 'nullable|string',
            'access_token' => 'nullable|string',
            'app_id' => 'nullable|string',
            'app_secret' => 'nullable|string',
        ]);

        $data['webhook_verify_token'] = Str::random(32);

        Device::create($data);

        return redirect()->route('devices.index')->with('success', 'Device berhasil ditambahkan!');
    }

    public function show(Device $device)
    {
        $waService = new WhatsAppService($device);

        // Fetch info from Meta API
        $phoneInfo = null;
        $wabaInfo = null;
        $businessProfile = null;

        if ($device->phone_number_id && $device->access_token) {
            $phoneResult = $waService->getPhoneNumberInfo();
            $phoneInfo = $phoneResult['success'] ? $phoneResult['data'] : ['_error' => $phoneResult['error']];
        }

        if ($device->waba_id && $device->access_token) {
            $wabaResult = $waService->getWABAInfo();
            $wabaInfo = $wabaResult['success'] ? $wabaResult['data'] : ['_error' => $wabaResult['error']];

            $businessResult = $waService->getBusinessProfile();
            $businessProfile = $businessResult['success'] ? $businessResult['data'] : null;
        }

        // Local stats
        $localStats = [
            'templates_total' => MessageTemplate::where('device_id', $device->id)->count(),
            'templates_approved' => MessageTemplate::where('device_id', $device->id)->where('status', 'APPROVED')->count(),
            'broadcasts' => Broadcast::where('device_id', $device->id)->count(),
            'messages_received' => IncomingMessage::where('device_id', $device->id)->count(),
            'total_sent' => Broadcast::where('device_id', $device->id)->sum('sent'),
            'total_delivered' => Broadcast::where('device_id', $device->id)->sum('delivered'),
            'total_failed' => Broadcast::where('device_id', $device->id)->sum('failed'),
        ];

        return view('devices.show', compact('device', 'phoneInfo', 'wabaInfo', 'businessProfile', 'localStats'));
    }

    public function edit(Device $device)
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'waba_id' => 'nullable|string',
            'phone_number_id' => 'nullable|string',
            'access_token' => 'nullable|string',
            'app_id' => 'nullable|string',
            'app_secret' => 'nullable|string',
        ]);

        $device->update($data);

        return redirect()->route('devices.index')->with('success', 'Device berhasil diupdate!');
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Device berhasil dihapus!');
    }
}
