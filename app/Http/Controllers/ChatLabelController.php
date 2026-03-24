<?php

namespace App\Http\Controllers;

use App\Models\ChatLabel;
use App\Models\Device;
use Illuminate\Http\Request;

class ChatLabelController extends Controller
{
    private function getDevices()
    {
        if (auth()->user()->isSuperAdmin()) {
            return Device::all();
        }
        return auth()->user()->devices;
    }

    public function index(Request $request)
    {
        $devices = $this->getDevices();
        if ($devices->isEmpty()) {
            return back()->with('error', 'Anda belum memiliki akses ke device manapun.');
        }

        $deviceId = $request->get('device_id', $devices->first()->id);
        
        $labels = ChatLabel::where('device_id', $deviceId)->latest()->get();

        return view('chat_labels.index', compact('devices', 'deviceId', 'labels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'name' => 'required|string|max:50',
            'color_hex' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/i'
        ]);

        // Security check
        $devices = $this->getDevices();
        if (!$devices->contains('id', $request->device_id)) {
            abort(403);
        }

        ChatLabel::create($request->only('device_id', 'name', 'color_hex'));

        return back()->with('success', 'Label baru berhasil dibuat.');
    }

    public function update(Request $request, ChatLabel $chatLabel)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'color_hex' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/i'
        ]);

        $devices = $this->getDevices();
        if (!$devices->contains('id', $chatLabel->device_id)) {
            abort(403);
        }

        $chatLabel->update($request->only('name', 'color_hex'));

        return back()->with('success', 'Label berhasil diperbarui.');
    }

    public function destroy(ChatLabel $chatLabel)
    {
        $devices = $this->getDevices();
        if (!$devices->contains('id', $chatLabel->device_id)) {
            abort(403);
        }

        $chatLabel->delete();

        return back()->with('success', 'Label berhasil dihapus.');
    }
}
