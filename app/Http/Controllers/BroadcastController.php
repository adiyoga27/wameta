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

        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $contacts = $isSuperAdmin
            ? Contact::with('category')->latest()->get()
            : Contact::with('category')->where('user_id', $user->id)->latest()->get();

        $categories = $isSuperAdmin
            ? \App\Models\ContactCategory::all()
            : \App\Models\ContactCategory::where('user_id', $user->id)->get();

        return view('broadcasts.create', compact('devices', 'deviceId', 'templates', 'contacts', 'categories'));
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
        $broadcast->load(['messageTemplate', 'device', 'broadcastContacts.contact', 'user']);

        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $contacts = $isSuperAdmin
            ? Contact::with('category')->latest()->get()
            : Contact::with('category')->where('user_id', $user->id)->latest()->get();

        $categories = $isSuperAdmin
            ? \App\Models\ContactCategory::all()
            : \App\Models\ContactCategory::where('user_id', $user->id)->get();

        return view('broadcasts.show', compact('broadcast', 'contacts', 'categories'));
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

        // Persiapkan parameter header jika template butuh lampiran media
        $headerData = [];
        if (in_array($template->header_type, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
            if ($template->header_media_path) {
                $filePath = storage_path('app/public/' . $template->header_media_path);
                if (file_exists($filePath)) {
                    $mimeType = mime_content_type($filePath);
                    $uploadResult = $waService->uploadMedia($filePath, $mimeType);
                    
                    if ($uploadResult['success']) {
                        $mediaId = $uploadResult['media_id'];
                        $typeKey = strtolower($template->header_type); // image, video, document
                        $headerData = [
                            'type' => $typeKey,
                            $typeKey => ['id' => $mediaId]
                        ];
                    } else {
                        // Jika gagal upload media, broadcast tidak bisa jalan
                        $broadcast->update(['status' => 'failed']);
                        return back()->with('error', 'Gagal memproses media template untuk broadcast: ' . ($uploadResult['error'] ?? 'Unknown error'));
                    }
                }
            }
        }

        $sent = 0;
        $failed = 0;

        foreach ($broadcast->broadcastContacts()->whereIn('status', ['pending', 'failed'])->get() as $bc) {
            $contact = $bc->contact;

            $result = $waService->sendTemplateMessage(
                $contact->phone,
                $template->name,
                $template->language,
                [], // parameters (body params), currently unused
                $headerData
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
            'sent' => $broadcast->broadcastContacts()->whereIn('status', ['sent', 'delivered', 'read'])->count(),
            'failed' => $broadcast->broadcastContacts()->where('status', 'failed')->count(),
            'delivered' => $broadcast->broadcastContacts()->whereIn('status', ['delivered', 'read'])->count(),
            'read' => $broadcast->broadcastContacts()->where('status', 'read')->count(),
        ]);

        return back()->with('success', "Broadcast selesai memproses kontak!");
    }

    public function addContacts(Request $request, Broadcast $broadcast)
    {
        $request->validate([
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $added = 0;
        foreach ($request->contact_ids as $contactId) {
            $exists = $broadcast->broadcastContacts()->where('contact_id', $contactId)->exists();
            if (!$exists) {
                BroadcastContact::create([
                    'broadcast_id' => $broadcast->id,
                    'contact_id' => $contactId,
                    'status' => 'pending',
                ]);
                $added++;
            }
        }

        if ($added > 0) {
            $broadcast->increment('total', $added);
            if ($broadcast->status === 'completed') {
                $broadcast->update(['status' => 'draft']);
            }
            return back()->with('success', "{$added} kontak baru berhasil ditambahkan ke broadcast.");
        }

        return back()->with('info', 'Kontak yang dipilih sudah ada di broadcast ini.');
    }

    public function resetContact(BroadcastContact $broadcastContact)
    {
        $broadcastContact->update([
            'status' => 'pending',
            'error_message' => null,
            'wa_message_id' => null,
        ]);

        $broadcast = $broadcastContact->broadcast;
        $broadcast->update([
            'status' => 'draft',
            'sent' => $broadcast->broadcastContacts()->whereIn('status', ['sent', 'delivered', 'read'])->count(),
            'failed' => $broadcast->broadcastContacts()->where('status', 'failed')->count(),
            'delivered' => $broadcast->broadcastContacts()->whereIn('status', ['delivered', 'read'])->count(),
            'read' => $broadcast->broadcastContacts()->where('status', 'read')->count(),
        ]);

        return back()->with('success', 'Status kontak berhasil dikembalikan ke Pending.');
    }
}
