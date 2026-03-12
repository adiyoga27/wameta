<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MessageTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class TemplateController extends Controller
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

        $templates = MessageTemplate::where('device_id', $deviceId)->latest()->get();

        return view('templates.index', compact('templates', 'devices', 'deviceId'));
    }

    public function create(Request $request)
    {
        $devices = $this->getDevices();
        $deviceId = $request->get('device_id', $devices->first()?->id);

        return view('templates.create', compact('devices', 'deviceId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'name' => 'required|string|max:512|regex:/^[a-z0-9_]+$/',
            'language' => 'required|string',
            'category' => 'required|in:MARKETING,UTILITY,AUTHENTICATION',
            'header_type' => 'required|in:NONE,TEXT,IMAGE,VIDEO,DOCUMENT',
            'header_content' => 'nullable|string',
            'header_media' => 'nullable|file|max:102400', // max 100MB for documents
            'body' => 'required|string',
            'footer' => 'nullable|string|max:60',
            'buttons' => 'nullable|array|max:3',
            'buttons.*.type' => 'required_with:buttons|in:QUICK_REPLY,URL,PHONE_NUMBER,COPY_CODE,FLOW',
            'buttons.*.text' => 'required_with:buttons|string|max:25',
            'buttons.*.url' => 'nullable|required_if:buttons.*.type,URL|url|max:2000',
            'buttons.*.phone_number' => 'nullable|required_if:buttons.*.type,PHONE_NUMBER|string|max:20',
            'buttons.*.copy_code' => 'nullable|required_if:buttons.*.type,COPY_CODE|string|max:15',
            'buttons.*.flow_id' => 'nullable|required_if:buttons.*.type,FLOW|string',
            'buttons.*.flow_action' => 'nullable|in:navigate,data_exchange',
        ]);

        $device = Device::findOrFail($data['device_id']);
        $waService = new WhatsAppService($device);

        // Handle media file upload — upload to Meta Resumable Upload API
        $headerMediaPath = null;
        $headerHandle = null;

        if ($request->hasFile('header_media') && in_array($data['header_type'], ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
            $file = $request->file('header_media');
            // Store locally first
            $headerMediaPath = $file->store('template-media', 'public');

            // Upload to Meta via Resumable Upload API to get the handle
            $filePath = storage_path('app/public/' . $headerMediaPath);
            $uploadResult = $waService->uploadMediaForHandle(
                $filePath,
                $file->getClientOriginalName(),
                $file->getSize(),
                $file->getMimeType()
            );

            if (!$uploadResult['success']) {
                $errorJson = isset($uploadResult['full_response']) ? json_encode($uploadResult['full_response'], JSON_PRETTY_PRINT) : 'N/A';
                return redirect()->route('templates.index', ['device_id' => $data['device_id']])
                    ->with('error', 'Gagal upload media ke Meta: ' . ($uploadResult['error'] ?? 'Unknown error'))
                    ->with('error_json', $errorJson);
            }

            $headerHandle = $uploadResult['handle'];
        }

        // Pass the handle to build components
        $data['header_handle'] = $headerHandle;

        // Submit to Meta API
        $result = $waService->createTemplate($data);

        // Build rejected reason with full JSON for debugging
        $rejectedReason = null;
        if (!$result['success']) {
            $rejectedReason = ($result['error'] ?? 'Unknown error');
            if (!empty($result['error_detail'])) {
                $rejectedReason .= ' — ' . $result['error_detail'];
            }
            if (!empty($result['full_response'])) {
                $rejectedReason .= "\n\n--- Full API Response ---\n" . json_encode($result['full_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }

        $template = MessageTemplate::create([
            'device_id' => $data['device_id'],
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => $data['category'],
            'header_type' => $data['header_type'],
            'header_content' => $data['header_content'] ?? null,
            'header_media_path' => $headerMediaPath,
            'body' => $data['body'],
            'footer' => $data['footer'] ?? null,
            'buttons' => $request->input('buttons'),
            'status' => $result['success'] ? 'PENDING' : 'REJECTED',
            'rejected_reason' => $rejectedReason,
            'meta_template_id' => $result['data']['id'] ?? null,
        ]);

        if ($result['success']) {
            return redirect()->route('templates.index', ['device_id' => $data['device_id']])
                ->with('success', 'Template berhasil diajukan ke Meta untuk review!');
        }

        $errorMsg = 'Gagal mengajukan template: ' . ($result['error'] ?? 'Unknown error');
        if (!empty($result['full_response'])) {
            $errorJson = json_encode($result['full_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return redirect()->route('templates.index', ['device_id' => $data['device_id']])
                ->with('error', $errorMsg)
                ->with('error_json', $errorJson);
        }

        return redirect()->route('templates.index', ['device_id' => $data['device_id']])
            ->with('error', $errorMsg);
    }

    public function sync(Request $request, $id)
    {
        $template = MessageTemplate::findOrFail($id);
        $device = $template->device;
        $waService = new WhatsAppService($device);

        $result = $waService->getTemplates();

        if ($result['success']) {
            foreach ($result['data'] as $metaTemplate) {
                if ($metaTemplate['name'] === $template->name) {
                    $template->update([
                        'status' => strtoupper($metaTemplate['status']),
                        'rejected_reason' => $metaTemplate['quality_score']['reasons'][0] ?? null,
                        'meta_template_id' => $metaTemplate['id'] ?? $template->meta_template_id,
                    ]);
                    return back()->with('success', 'Status template berhasil di-sync! Status: ' . $metaTemplate['status']);
                }
            }
            return back()->with('error', 'Template tidak ditemukan di Meta.');
        }

        return back()->with('error', 'Gagal sync: ' . ($result['error'] ?? 'Unknown error'));
    }

    public function destroy($id)
    {
        $template = MessageTemplate::findOrFail($id);
        $device = $template->device;
        $deviceId = $device->id;

        $waService = new WhatsAppService($device);
        $result = $waService->deleteTemplate($template->name);

        $template->delete();

        if ($result['success']) {
            return redirect()->route('templates.index', ['device_id' => $deviceId])
                ->with('success', 'Template berhasil dihapus!');
        }

        return redirect()->route('templates.index', ['device_id' => $deviceId])
            ->with('warning', 'Template dihapus dari database tetapi gagal dihapus dari Meta: ' . ($result['error'] ?? ''));
    }

    /**
     * Sync ALL templates from Meta API:
     * - Update status of existing local templates
     * - Import templates created outside the app
     */
    public function syncAll(Request $request)
    {
        $deviceId = $request->input('device_id');
        $device = Device::findOrFail($deviceId);
        $waService = new WhatsAppService($device);

        $result = $waService->getTemplates();

        if (!$result['success']) {
            return back()->with('error', 'Gagal sync dari Meta: ' . ($result['error'] ?? 'Unknown error'));
        }

        $metaTemplates = $result['data'] ?? [];
        $synced = 0;
        $created = 0;
        $updated = 0;

        foreach ($metaTemplates as $mt) {
            $templateName = $mt['name'] ?? null;
            if (!$templateName) continue;

            $language = $mt['language'] ?? 'id';
            $status = strtoupper($mt['status'] ?? 'PENDING');
            $category = strtoupper($mt['category'] ?? 'MARKETING');
            $metaId = $mt['id'] ?? null;

            // Extract body, header, footer from components
            $body = '';
            $headerType = 'NONE';
            $headerContent = null;
            $footer = null;
            $buttons = null;

            if (isset($mt['components'])) {
                foreach ($mt['components'] as $component) {
                    $type = strtoupper($component['type'] ?? '');
                    if ($type === 'BODY') {
                        $body = $component['text'] ?? '';
                    } elseif ($type === 'HEADER') {
                        $headerType = strtoupper($component['format'] ?? 'TEXT');
                        $headerContent = $component['text'] ?? null;
                    } elseif ($type === 'FOOTER') {
                        $footer = $component['text'] ?? null;
                    } elseif ($type === 'BUTTONS') {
                        $buttons = $component['buttons'] ?? null;
                    }
                }
            }

            // Rejected reason
            $rejectedReason = null;
            if (isset($mt['quality_score']['reasons']) && !empty($mt['quality_score']['reasons'])) {
                $rejectedReason = implode(', ', $mt['quality_score']['reasons']);
            }
            if (isset($mt['rejected_reason'])) {
                $rejectedReason = $mt['rejected_reason'];
            }

            // Find existing template by name + language + device
            $existing = MessageTemplate::where('device_id', $deviceId)
                ->where('name', $templateName)
                ->where('language', $language)
                ->first();

            if ($existing) {
                // Update existing
                $existing->update([
                    'status' => $status,
                    'category' => $category,
                    'rejected_reason' => $rejectedReason,
                    'meta_template_id' => $metaId ?? $existing->meta_template_id,
                    'body' => $body ?: $existing->body,
                    'header_type' => $headerType ?: $existing->header_type,
                    'header_content' => $headerContent ?? $existing->header_content,
                    'footer' => $footer ?? $existing->footer,
                    'buttons' => $buttons ?? $existing->buttons,
                ]);
                $updated++;
            } else {
                // Create new — template was created outside this app
                MessageTemplate::create([
                    'device_id' => $deviceId,
                    'name' => $templateName,
                    'language' => $language,
                    'category' => $category,
                    'header_type' => $headerType,
                    'header_content' => $headerContent,
                    'body' => $body ?: '-',
                    'footer' => $footer,
                    'buttons' => $buttons,
                    'status' => $status,
                    'rejected_reason' => $rejectedReason,
                    'meta_template_id' => $metaId,
                ]);
                $created++;
            }
            $synced++;
        }

        return back()->with('success', "Sync selesai! Total dari Meta: {$synced} template. Diupdate: {$updated}, Baru diimport: {$created}.");
    }
}
