@extends('layouts.app')

@section('title', 'Chat — ' . ($contactName ?? $contactNumber))

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
    {{-- Sidebar --}}
    <div class="chat-sidebar" id="chatSidebar">
        <div class="chat-sidebar-header">
            <h4 style="font-size:15px;font-weight:700;"><i class="bi bi-chat-dots-fill" style="color:var(--accent);margin-right:6px;"></i> Percakapan</h4>
            <span style="font-size:12px;color:var(--text-muted);">{{ $conversations->count() }}</span>
        </div>
        <div class="conversation-list">
            @foreach($conversations as $conv)
            <a href="{{ route('messages.show', ['deviceId' => $deviceId, 'contactNumber' => $conv->contact_number]) }}"
               class="conversation-item {{ $conv->contact_number === $contactNumber ? 'active' : '' }}">
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
    </div>

    {{-- Chat Area --}}
    <div class="chat-main">
        {{-- Chat Header --}}
        <div class="chat-header">
            <button class="sidebar-back-btn" onclick="document.getElementById('chatSidebar').classList.toggle('show-mobile')">
                <i class="bi bi-arrow-left"></i>
            </button>
            <div class="conv-avatar" style="width:38px;height:38px;min-width:38px;font-size:14px;">
                {{ strtoupper(substr($contactName, 0, 1)) }}
            </div>
            <div>
                <div style="font-size:14px;font-weight:700;">{{ $contactName }}</div>
                <div style="font-size:12px;color:var(--text-muted);">{{ $contactNumber }}</div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="chat-messages" id="chatMessages">
            @php $lastDate = null; @endphp
            @foreach($messages as $msg)
                @php
                    $msgDate = ($msg->wa_timestamp ?? $msg->created_at)->format('d M Y');
                @endphp
                @if($msgDate !== $lastDate)
                    <div class="chat-date-divider">
                        <span>{{ $msgDate }}</span>
                    </div>
                    @php $lastDate = $msgDate; @endphp
                @endif

                <div class="chat-bubble {{ $msg->direction === 'out' ? 'bubble-out' : 'bubble-in' }}">
                    @if($msg->message_type !== 'text')
                        <div class="bubble-media-tag">
                            @switch($msg->message_type)
                                @case('image') <i class="bi bi-image"></i> Foto @break
                                @case('video') <i class="bi bi-camera-video"></i> Video @break
                                @case('audio') <i class="bi bi-mic"></i> Audio @break
                                @case('document') <i class="bi bi-file-earmark"></i> Dokumen @break
                                @case('sticker') <i class="bi bi-emoji-smile"></i> Stiker @break
                                @case('location') <i class="bi bi-geo-alt"></i> Lokasi @break
                                @case('contacts') <i class="bi bi-person"></i> Kontak @break
                                @case('reaction') <i class="bi bi-emoji-heart-eyes"></i> Reaksi @break
                                @default <i class="bi bi-chat"></i> {{ $msg->message_type }} @break
                            @endswitch
                        </div>
                    @endif
                    <div class="bubble-text">{{ $msg->message_body }}</div>
                    <div class="bubble-time">
                        {{ ($msg->wa_timestamp ?? $msg->created_at)->format('H:i') }}
                        @if($msg->direction === 'out')
                            @switch($msg->status)
                                @case('sent') <i class="bi bi-check2"></i> @break
                                @case('delivered') <i class="bi bi-check2-all"></i> @break
                                @case('read') <i class="bi bi-check2-all" style="color:#53bdeb;"></i> @break
                                @case('failed') <i class="bi bi-x-circle" style="color:var(--danger);"></i> @break
                            @endswitch
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Input Area --}}
        <div class="chat-input-area">
            <form method="POST" action="{{ route('messages.send') }}" class="chat-input-form" id="chatForm">
                @csrf
                <input type="hidden" name="device_id" value="{{ $deviceId }}">
                <input type="hidden" name="contact_number" value="{{ $contactNumber }}">
                <input type="text" name="message" class="chat-input" placeholder="Ketik pesan..." autocomplete="off" required id="messageInput">
                <button type="submit" class="chat-send-btn" id="sendBtn">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.chat-container { display:flex; height:calc(100vh - 130px); background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }

/* Sidebar */
.chat-sidebar { width:340px; min-width:340px; border-right:1px solid var(--border); display:flex; flex-direction:column; }
.chat-sidebar-header { padding:16px 18px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
.conversation-list { flex:1; overflow-y:auto; }
.conversation-item { display:flex; align-items:center; gap:12px; padding:14px 18px; text-decoration:none; color:var(--text-primary); transition:background 0.15s; border-bottom:1px solid var(--border); }
.conversation-item:hover { background:var(--bg-glass); color:var(--text-primary); }
.conversation-item.active { background:var(--accent-soft); border-left:3px solid var(--accent); }
.conv-avatar { width:44px; height:44px; min-width:44px; border-radius:50%; background:linear-gradient(135deg, var(--accent), #128c50); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; color:white; }
.conv-info { flex:1; min-width:0; }
.conv-name { font-size:14px; font-weight:600; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-preview { font-size:12px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-meta { text-align:right; min-width:50px; }
.conv-time { font-size:11px; color:var(--text-muted); margin-bottom:4px; }
.conv-badge { background:var(--accent); color:#000; font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; display:inline-block; }

/* Chat Area */
.chat-main { flex:1; display:flex; flex-direction:column; min-width:0; }
.chat-header { padding:12px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; background:var(--bg-secondary); }
.sidebar-back-btn { display:none; background:none; border:none; color:var(--text-primary); font-size:18px; cursor:pointer; padding:4px; }

/* Messages */
.chat-messages { flex:1; overflow-y:auto; padding:20px; background:var(--bg-primary); background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }

.chat-date-divider { text-align:center; margin:16px 0; }
.chat-date-divider span { background:rgba(255,255,255,0.06); padding:4px 14px; border-radius:8px; font-size:11px; color:var(--text-muted); font-weight:500; }

.chat-bubble { max-width:70%; padding:8px 12px; border-radius:12px; margin-bottom:4px; position:relative; animation:fadeInUp 0.2s ease; word-wrap:break-word; }
.bubble-in { background:var(--bg-secondary); border:1px solid var(--border); align-self:flex-start; border-radius:4px 12px 12px 12px; margin-right:auto; }
.bubble-out { background:#005c4b; align-self:flex-end; border-radius:12px 4px 12px 12px; margin-left:auto; }
.bubble-text { font-size:14px; line-height:1.5; white-space:pre-wrap; }
.bubble-time { font-size:10px; color:rgba(255,255,255,0.5); text-align:right; margin-top:2px; display:flex; align-items:center; justify-content:flex-end; gap:3px; }
.bubble-in .bubble-time { color:var(--text-muted); }
.bubble-media-tag { font-size:11px; color:var(--text-muted); margin-bottom:4px; display:flex; align-items:center; gap:4px; opacity:0.8; }
.bubble-out .bubble-media-tag { color:rgba(255,255,255,0.6); }

/* Input */
.chat-input-area { padding:12px 20px; border-top:1px solid var(--border); background:var(--bg-secondary); }
.chat-input-form { display:flex; gap:10px; align-items:center; }
.chat-input { flex:1; padding:12px 16px; background:var(--bg-primary); border:1px solid var(--border); border-radius:24px; color:var(--text-primary); font-size:14px; font-family:'Inter',sans-serif; outline:none; transition:border-color 0.2s; }
.chat-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
.chat-input::placeholder { color:var(--text-muted); }
.chat-send-btn { width:44px; height:44px; min-width:44px; border-radius:50%; background:var(--accent); border:none; color:#000; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
.chat-send-btn:hover { background:var(--accent-hover); transform:scale(1.05); }

.chat-messages { display:flex; flex-direction:column; }

@keyframes fadeInUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

@media (max-width:768px) {
    .chat-sidebar { position:absolute; left:0; top:0; width:100%; height:100%; z-index:10; background:var(--bg-card); display:none; }
    .chat-sidebar.show-mobile { display:flex; }
    .sidebar-back-btn { display:block; }
    .chat-bubble { max-width:85%; }
}
</style>
@endsection

@section('scripts')
<script>
// Auto-scroll to bottom
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Focus on input
const messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.focus();
}
</script>
@endsection
