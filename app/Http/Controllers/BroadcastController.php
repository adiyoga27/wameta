<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\BroadcastContact;
use App\Models\Contact;
use App\Models\Device;
use App\Models\MessageTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    protected function getDevices()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? Device::all() : $user->devices;
    }

    public function index(Request $request)
    {
        $devices = $this->getDevices();
        $deviceId = $request->get('device_id', $devices->first()?->id);

        $broadcasts = Broadcast::where('device_id', $deviceId)
            ->with(['messageTemplate', 'user'])
            ->latest()
            ->get();

        return view('broadcasts.index', compact('broadcasts', 'devices', 'deviceId'));
    }

    public function create(Request $request)
    {
        $devices = $this->getDevices();
        $deviceId = $request->get('device_id', $devices->first()?->id);

        $templates = MessageTemplate::where('device_id', $deviceId)
            ->where('status', 'APPROVED')
            ->get();

        $contacts = Contact::where('user_id', auth()->id())->get();

        return view('broadcasts.create', compact('devices', 'deviceId', 'templates', 'contacts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'name' => 'required|string|max:255',
            'message_template_id' => 'required|exists:message_templates,id',
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $broadcast = Broadcast::create([
            'device_id' => $data['device_id'],
            'user_id' => auth()->id(),
            'message_template_id' => $data['message_template_id'],
            'name' => $data['name'],
            'status' => 'draft',
            'total' => count($data['contact_ids']),
        ]);

        foreach ($data['contact_ids'] as $contactId) {
            BroadcastContact::create([
                'broadcast_id' => $broadcast->id,
                'contact_id' => $contactId,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('broadcasts.show', $broadcast->id)
            ->with('success', 'Broadcast berhasil dibuat! Klik "Kirim Sekarang" untuk mulai mengirim.');
    }

    public function show(Broadcast $broadcast)
    {
        $broadcast->load(['messageTemplate', 'device', 'broadcastContacts.contact']);
        return view('broadcasts.show', compact('broadcast'));
    }

    public function send(Broadcast $broadcast)
    {
        if ($broadcast->status === 'sending') {
            return back()->with('error', 'Broadcast sedang dalam proses pengiriman!');
        }

        $broadcast->update(['status' => 'sending']);

        $device = $broadcast->device;
        $template = $broadcast->messageTemplate;
        $waService = new WhatsAppService($device);

        $sent = 0;
        $failed = 0;

        foreach ($broadcast->broadcastContacts()->where('status', 'pending')->get() as $bc) {
            $contact = $bc->contact;

            $result = $waService->sendTemplateMessage(
                $contact->phone,
                $template->name,
                $template->language
            );

            if ($result['success']) {
                $bc->update([
                    'status' => 'sent',
                    'wa_message_id' => $result['message_id'] ?? null,
                ]);
                $sent++;
            } else {
                $bc->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error',
                ]);
                $failed++;
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 100ms
        }

        $broadcast->update([
            'status' => 'completed',
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return back()->with('success', "Broadcast selesai! Terkirim: {$sent}, Gagal: {$failed}");
    }
}
