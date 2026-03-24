@extends('layouts.app')

@section('title', 'Preview Template: ' . $template->name)

@section('content')
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3><i class="bi bi-eye-fill" style="color:var(--accent);margin-right:8px;"></i> Preview Template: {{ $template->name }}</h3>
    </div>
    
    <div class="card-body" style="padding: 24px;">
        <div style="margin-bottom: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
                <span style="font-size: 12px; color: var(--text-muted); display: block;">Perangkat</span>
                <strong>{{ $template->device->name }}</strong>
            </div>
            <div>
                <span style="font-size: 12px; color: var(--text-muted); display: block;">Bahasa</span>
                <strong>{{ $template->language }}</strong>
            </div>
            <div>
                <span style="font-size: 12px; color: var(--text-muted); display: block;">Kategori</span>
                <span class="badge badge-purple">{{ $template->category }}</span>
            </div>
            <div>
                <span style="font-size: 12px; color: var(--text-muted); display: block;">Status Meta</span>
                @switch($template->status)
                    @case('APPROVED')
                        <span class="badge badge-success"><i class="bi bi-check-circle"></i> Approved</span>
                        @break
                    @case('REJECTED')
                        <span class="badge badge-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                        @break
                    @case('PENDING')
                        <span class="badge badge-warning"><i class="bi bi-clock"></i> Pending</span>
                        @break
                    @case('PAUSED')
                        <span class="badge badge-info"><i class="bi bi-pause-circle"></i> Paused</span>
                        @break
                    @default
                        <span class="badge badge-secondary">{{ $template->status }}</span>
                @endswitch
            </div>
        </div>
        
        @if($template->rejected_reason)
            <div style="background: rgba(255,71,87,0.1); border: 1px solid var(--danger); border-radius: 8px; padding: 12px; margin-bottom: 24px;">
                <strong style="color: var(--danger); font-size: 13px; display: block; margin-bottom: 4px;"><i class="bi bi-exclamation-triangle-fill"></i> Alasan Ditolak / Keterangan:</strong>
                <div style="font-size: 13px; color: var(--text-secondary); white-space: pre-wrap;">{{ Str::before($template->rejected_reason, "\n\n--- Full API Response ---") }}</div>
            </div>
        @endif

        <div style="background:#0b141a;border-radius:12px;padding:16px;max-width:400px;margin: 0 auto;">
            <div style="background:#005c4b;color:white;padding:10px 14px;border-radius:8px 8px 8px 2px;font-size:14px;line-height:1.5;">
                @if($template->header_type === 'TEXT')
                    <div style="font-weight:bold;margin-bottom:8px;">{{ $template->header_content }}</div>
                @elseif($template->header_type === 'IMAGE' && $template->header_media_path)
                    <div style="margin-bottom:8px; border-radius: 6px; overflow: hidden; max-height: 200px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2);">
                        <img src="{{ asset('storage/' . $template->header_media_path) }}" alt="Header Image" style="max-width: 100%; max-height: 200px; display: block; object-fit: contain;">
                    </div>
                @elseif($template->header_type === 'VIDEO' && $template->header_media_path)
                    <div style="margin-bottom:8px; border-radius: 6px; overflow: hidden; background: #000;">
                        <video controls style="width: 100%; max-height: 200px; display: block;">
                            <source src="{{ asset('storage/' . $template->header_media_path) }}" type="video/mp4">
                        </video>
                    </div>
                @elseif(in_array($template->header_type, ['IMAGE', 'VIDEO', 'DOCUMENT']))
                    <div style="margin-bottom:8px;font-style:italic;color:#e2e2e2;background:rgba(255,255,255,0.1);padding:10px;border-radius:6px;text-align:center;">
                        <i class="bi bi-file-earmark-{{ strtolower($template->header_type) }} dropdown-item-icon" style="font-size:24px;display:block;margin-bottom:4px;"></i> 
                        <div style="font-size:12px;font-weight:bold;">{{ $template->header_type }} MEDIA</div>
                        @if($template->header_media_path)
                            <a href="{{ asset('storage/' . $template->header_media_path) }}" target="_blank" style="font-size:11px; color:#53bdeb; text-decoration:none; display:inline-block; margin-top:6px; background:rgba(0,0,0,0.3); padding:4px 8px; border-radius:4px;"><i class="bi bi-box-arrow-up-right"></i> Buka File</a>
                        @endif
                    </div>
                @endif
                
                <div style="white-space:pre-wrap;">{{ $template->body }}</div>
                
                @if($template->footer)
                    <div style="font-size:12px;color:rgba(255,255,255,0.6);margin-top:8px;">{{ $template->footer }}</div>
                @endif
            </div>
            <div style="text-align:right;margin-top:4px;">
                <span style="font-size:11px;color:#8696a0;">12:00 <i class="bi bi-check2-all" style="color:#53bdeb;"></i></span>
            </div>
            
            @if($template->buttons && count($template->buttons) > 0)
                <div style="margin-top:8px;display:flex;flex-direction:column;gap:6px;">
                    @foreach($template->buttons as $btn)
                        <div style="background:rgba(0, 92, 75, 0.6);border:1px solid rgba(255,255,255,0.1);color:#53bdeb;padding:8px 12px;border-radius:8px;font-size:13px;text-align:center;">
                            @if(($btn['type'] ?? '') === 'URL') <i class="bi bi-box-arrow-up-right"></i>
                            @elseif(($btn['type'] ?? '') === 'PHONE_NUMBER') <i class="bi bi-telephone-fill"></i>
                            @elseif(($btn['type'] ?? '') === 'QUICK_REPLY') <i class="bi bi-reply-fill"></i>
                            @elseif(($btn['type'] ?? '') === 'COPY_CODE') <i class="bi bi-clipboard"></i>
                            @elseif(($btn['type'] ?? '') === 'FLOW') <i class="bi bi-diagram-3"></i>
                            @endif
                            {{ $btn['text'] ?? 'Button' }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    
    <div class="card-footer" style="margin-top: 16px; padding-top: 20px; text-align: center; border-top: 1px solid var(--border);">
        <a href="{{ route('templates.index', ['device_id' => $template->device_id]) }}" class="btn btn-secondary">Kembali</a>
        <a href="{{ route('templates.edit', $template->id) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
    </div>
</div>
@endsection
