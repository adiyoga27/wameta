@extends('layouts.app')

@section('title', 'Pesan')

@section('actions')
<form method="GET" action="{{ route('messages.index') }}" style="display:flex;gap:8px;align-items:center;">
    <select name="device_id" class="form-control" style="width:200px;padding:8px 12px;" onchange="this.form.submit()">
        @foreach($devices as $d)
            <option value="{{ $d->id }}" {{ $deviceId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
        @endforeach
    </select>
</form>
@endsection

@section('content')
<div class="chat-container">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h4 style="font-size:15px;font-weight:700;"><i class="bi bi-chat-dots-fill" style="color:var(--accent);margin-right:6px;"></i> Percakapan</h4>
            <span style="font-size:12px;color:var(--text-muted);">{{ $conversations->count() }} kontak</span>
        </div>
        @if($conversations->isEmpty())
            <div class="empty-state" style="padding:40px 20px;">
                <i class="bi bi-chat-dots" style="font-size:36px;"></i>
                <h4 style="font-size:14px;">Belum ada percakapan</h4>
                <p style="font-size:12px;">Pesan akan muncul saat ada webhook masuk</p>
            </div>
        @else
            <div class="conversation-list">
                @foreach($conversations as $conv)
                <a href="{{ route('messages.show', ['deviceId' => $deviceId, 'contactNumber' => $conv->contact_number]) }}" class="conversation-item">
                    <div class="conv-avatar">{{ strtoupper(substr($conv->contact_name ?? $conv->contact_number, 0, 1)) }}</div>
                    <div class="conv-info">
                        <div class="conv-name">{{ $conv->contact_name ?? $conv->contact_number }}</div>
                        <div class="conv-preview">
                            @if($conv->last_message)
                                @if($conv->last_message->direction === 'out')
                                    <i class="bi bi-check2-all" style="color:var(--accent);margin-right:2px;"></i>
                                @endif
                                {{ \Illuminate\Support\Str::limit($conv->last_message->message_body ?? '[Media]', 35) }}
                            @endif
                        </div>
                    </div>
                    <div class="conv-meta">
                        <div class="conv-time">{{ $conv->last_time ? \Carbon\Carbon::parse($conv->last_time)->format('H:i') : '' }}</div>
                        @if($conv->unread_count > 0)
                            <div class="conv-badge">{{ $conv->unread_count }}</div>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
        @endif
    </div>
    <div class="chat-main">
        <div class="chat-empty-state">
            <div style="text-align:center;color:var(--text-muted);">
                <i class="bi bi-chat-dots" style="font-size:64px;opacity:0.2;display:block;margin-bottom:16px;"></i>
                <h3 style="font-size:18px;color:var(--text-secondary);margin-bottom:8px;">Pilih percakapan</h3>
                <p style="font-size:13px;">Pilih kontak di sebelah kiri untuk mulai chat</p>
            </div>
        </div>
    </div>
</div>

<style>
.chat-container { display:flex; height:calc(100vh - 130px); background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
.chat-sidebar { width:340px; min-width:340px; border-right:1px solid var(--border); display:flex; flex-direction:column; }
.chat-sidebar-header { padding:16px 18px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
.conversation-list { flex:1; overflow-y:auto; }
.conversation-item { display:flex; align-items:center; gap:12px; padding:14px 18px; text-decoration:none; color:var(--text-primary); transition:background 0.15s; border-bottom:1px solid var(--border); cursor:pointer; }
.conversation-item:hover { background:var(--bg-glass); color:var(--text-primary); }
.conversation-item.active { background:var(--accent-soft); }
.conv-avatar { width:44px; height:44px; min-width:44px; border-radius:50%; background:linear-gradient(135deg, var(--accent), #128c50); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; color:white; }
.conv-info { flex:1; min-width:0; }
.conv-name { font-size:14px; font-weight:600; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-preview { font-size:12px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-meta { text-align:right; min-width:50px; }
.conv-time { font-size:11px; color:var(--text-muted); margin-bottom:4px; }
.conv-badge { background:var(--accent); color:#000; font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; display:inline-block; }
.chat-main { flex:1; display:flex; align-items:center; justify-content:center; }
.chat-empty-state { padding:40px; }
@media (max-width:768px) {
    .chat-sidebar { width:100%; min-width:100%; }
    .chat-main { display:none; }
}
</style>
@endsection
