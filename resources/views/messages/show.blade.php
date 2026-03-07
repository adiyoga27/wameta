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
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('newChatModal').style.display='flex'" style="padding:5px 12px;font-size:12px;">
                <i class="bi bi-plus-lg"></i> Baru
            </button>
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
            <div style="flex:1;">
                <div style="font-size:14px;font-weight:700;">{{ $contactName }}</div>
                <div style="font-size:12px;color:var(--text-muted);">{{ $contactNumber }}</div>
            </div>
            <div id="pollStatus" style="font-size:10px;color:var(--text-muted);display:flex;align-items:center;gap:4px;">
                <span class="poll-dot"></span> Live
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

                <div class="chat-bubble-row {{ $msg->direction === 'out' ? 'row-out' : 'row-in' }}" data-msg-id="{{ $msg->id }}">
                    @if($msg->direction === 'in')
                        <div class="bubble-avatar">{{ strtoupper(substr($contactName, 0, 1)) }}</div>
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
                                    @case('failed')
                                        <i class="bi bi-exclamation-circle" style="color:#ff4444;"></i>
                                        <button class="retry-btn" onclick="retryMessage({{ $msg->id }})" title="Kirim ulang">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    @break
                                @endswitch
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Input Area --}}
        <div class="chat-input-area">
            <form class="chat-input-form" id="chatForm" onsubmit="sendMessage(event)">
                @csrf
                <input type="text" name="message" class="chat-input" placeholder="Ketik pesan..." autocomplete="off" required id="messageInput">
                <button type="submit" class="chat-send-btn" id="sendBtn">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- New Chat Modal --}}
<div class="modal-overlay" id="newChatModal" style="display:none;">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="bi bi-chat-plus" style="color:var(--accent);margin-right:8px;"></i> Chat Baru</h3>
            <button class="modal-close" onclick="document.getElementById('newChatModal').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="{{ route('messages.send') }}">
            @csrf
            <input type="hidden" name="device_id" value="{{ $deviceId }}">
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp</label>
                <input type="text" name="contact_number" class="form-control" placeholder="628xxxxxxxxxx" required pattern="[0-9]+" title="Masukkan nomor tanpa + atau spasi">
                <div class="form-hint">Format: 628xxxxxxxxxx (tanpa + atau spasi)</div>
            </div>
            <div class="form-group">
                <label class="form-label">Pesan</label>
                <textarea name="message" class="form-control" rows="3" placeholder="Ketik pesan pertama..." required style="resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill"></i> Kirim</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newChatModal').style.display='none'">Batal</button>
            </div>
        </form>
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

/* Poll indicator */
.poll-dot { width:6px; height:6px; background:var(--accent); border-radius:50%; display:inline-block; animation:pulse 2s infinite; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.3; } }

/* Messages */
.chat-messages { flex:1; overflow-y:auto; padding:20px; background:var(--bg-primary); display:flex; flex-direction:column;
    background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.chat-date-divider { text-align:center; margin:16px 0; }
.chat-date-divider span { background:rgba(255,255,255,0.06); padding:4px 14px; border-radius:8px; font-size:11px; color:var(--text-muted); font-weight:500; }

/* Bubble rows with avatar */
.chat-bubble-row { display:flex; align-items:flex-end; gap:8px; margin-bottom:4px; }
.row-in { justify-content:flex-start; }
.row-out { justify-content:flex-end; }
.bubble-avatar { width:30px; height:30px; min-width:30px; border-radius:50%; background:linear-gradient(135deg, #6b7280, #4b5563); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:11px; color:white; margin-bottom:2px; }

.chat-bubble { max-width:65%; padding:8px 12px; border-radius:12px; position:relative; animation:fadeInUp 0.2s ease; word-wrap:break-word; }
.bubble-in { background:var(--bg-secondary); border:1px solid var(--border); border-radius:4px 12px 12px 12px; }
.bubble-out { background:#005c4b; border-radius:12px 4px 12px 12px; }
.bubble-text { font-size:14px; line-height:1.5; white-space:pre-wrap; }
.bubble-time { font-size:10px; color:rgba(255,255,255,0.5); text-align:right; margin-top:2px; display:flex; align-items:center; justify-content:flex-end; gap:3px; }
.bubble-in .bubble-time { color:var(--text-muted); }
.bubble-media-tag { font-size:11px; color:var(--text-muted); margin-bottom:4px; display:flex; align-items:center; gap:4px; opacity:0.8; }
.bubble-out .bubble-media-tag { color:rgba(255,255,255,0.6); }

/* Retry button */
.retry-btn { background:none; border:none; color:#ff4444; font-size:12px; cursor:pointer; padding:2px 4px; border-radius:4px; transition:all 0.2s; }
.retry-btn:hover { background:rgba(255,68,68,0.15); color:#ff6666; }

/* Input */
.chat-input-area { padding:12px 20px; border-top:1px solid var(--border); background:var(--bg-secondary); }
.chat-input-form { display:flex; gap:10px; align-items:center; }
.chat-input { flex:1; padding:12px 16px; background:var(--bg-primary); border:1px solid var(--border); border-radius:24px; color:var(--text-primary); font-size:14px; font-family:'Inter',sans-serif; outline:none; transition:border-color 0.2s; }
.chat-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
.chat-input::placeholder { color:var(--text-muted); }
.chat-send-btn { width:44px; height:44px; min-width:44px; border-radius:50%; background:var(--accent); border:none; color:#000; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
.chat-send-btn:hover { background:var(--accent-hover); transform:scale(1.05); }
.chat-send-btn:disabled { opacity:0.5; transform:none; cursor:not-allowed; }

/* Modal */
.modal-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.modal-content { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:24px; width:90%; }
.modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.modal-header h3 { font-size:16px; font-weight:700; }
.modal-close { background:none; border:none; color:var(--text-muted); font-size:24px; cursor:pointer; line-height:1; }

@keyframes fadeInUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

@media (max-width:768px) {
    .chat-sidebar { position:absolute; left:0; top:0; width:100%; height:100%; z-index:10; background:var(--bg-card); display:none; }
    .chat-sidebar.show-mobile { display:flex; }
    .sidebar-back-btn { display:block; }
    .chat-bubble { max-width:85%; }
    .bubble-avatar { width:24px; height:24px; min-width:24px; font-size:9px; }
}
</style>
@endsection

@section('scripts')
<script>
const DEVICE_ID = '{{ $deviceId }}';
const CONTACT_NUMBER = '{{ $contactNumber }}';
const CONTACT_NAME = '{{ $contactName }}';
const CSRF_TOKEN = '{{ csrf_token() }}';
const SEND_URL = '{{ route("messages.send") }}';
const POLL_URL = '{{ route("messages.poll", ["deviceId" => $deviceId, "contactNumber" => $contactNumber]) }}';

let lastMessageId = {{ $messages->last()?->id ?? 0 }};
const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');

// Auto-scroll to bottom
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
scrollToBottom();
messageInput.focus();

// Send message via AJAX
function sendMessage(e) {
    e.preventDefault();
    const msg = messageInput.value.trim();
    if (!msg) return;

    sendBtn.disabled = true;
    messageInput.disabled = true;

    // Optimistic: add bubble immediately
    const tempId = 'temp-' + Date.now();
    appendBubble({
        id: tempId,
        direction: 'out',
        message_type: 'text',
        message_body: msg,
        status: 'sending',
        wa_timestamp: new Date().toISOString(),
    });
    messageInput.value = '';
    scrollToBottom();

    fetch(SEND_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: JSON.stringify({ device_id: DEVICE_ID, contact_number: CONTACT_NUMBER, message: msg })
    })
    .then(r => r.json())
    .then(data => {
        // Remove temp bubble
        const tempEl = document.querySelector(`[data-msg-id="${tempId}"]`);
        if (tempEl) tempEl.remove();

        if (data.message) {
            appendBubble(data.message);
            lastMessageId = Math.max(lastMessageId, data.message.id);
        }

        if (!data.success && data.error) {
            showToast('Gagal: ' + data.error, 'error');
        }
        scrollToBottom();
    })
    .catch(err => {
        showToast('Gagal mengirim pesan', 'error');
    })
    .finally(() => {
        sendBtn.disabled = false;
        messageInput.disabled = false;
        messageInput.focus();
    });
}

// Retry failed message
function retryMessage(msgId) {
    const btn = document.querySelector(`[data-msg-id="${msgId}"] .retry-btn`);
    if (btn) btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

    fetch(`/messages/${msgId}/retry`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update bubble status
            const row = document.querySelector(`[data-msg-id="${msgId}"]`);
            if (row) {
                const timeEl = row.querySelector('.bubble-time');
                if (timeEl) {
                    const retryBtn = timeEl.querySelector('.retry-btn');
                    const failIcon = timeEl.querySelector('.bi-exclamation-circle');
                    if (retryBtn) retryBtn.remove();
                    if (failIcon) { failIcon.className = 'bi bi-check2'; failIcon.style.color = ''; }
                }
            }
            showToast('Pesan berhasil dikirim ulang!', 'success');
        } else {
            if (btn) btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
            showToast('Gagal kirim ulang: ' + (data.error || ''), 'error');
        }
    })
    .catch(() => {
        if (btn) btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
        showToast('Gagal mengirim ulang', 'error');
    });
}

// Append a bubble to the chat
function appendBubble(msg) {
    const row = document.createElement('div');
    row.className = 'chat-bubble-row ' + (msg.direction === 'out' ? 'row-out' : 'row-in');
    row.setAttribute('data-msg-id', msg.id);

    let avatarHtml = '';
    if (msg.direction === 'in') {
        const initial = (CONTACT_NAME || CONTACT_NUMBER).charAt(0).toUpperCase();
        avatarHtml = `<div class="bubble-avatar">${initial}</div>`;
    }

    const time = msg.wa_timestamp ? new Date(msg.wa_timestamp).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) : '';
    let statusIcon = '';
    if (msg.direction === 'out') {
        switch(msg.status) {
            case 'sending': statusIcon = '<i class="bi bi-clock"></i>'; break;
            case 'sent': statusIcon = '<i class="bi bi-check2"></i>'; break;
            case 'delivered': statusIcon = '<i class="bi bi-check2-all"></i>'; break;
            case 'read': statusIcon = '<i class="bi bi-check2-all" style="color:#53bdeb;"></i>'; break;
            case 'failed': statusIcon = `<i class="bi bi-exclamation-circle" style="color:#ff4444;"></i><button class="retry-btn" onclick="retryMessage(${msg.id})" title="Kirim ulang"><i class="bi bi-arrow-clockwise"></i></button>`; break;
        }
    }

    let mediaTag = '';
    if (msg.message_type !== 'text') {
        const icons = { image:'bi-image', video:'bi-camera-video', audio:'bi-mic', document:'bi-file-earmark', sticker:'bi-emoji-smile', location:'bi-geo-alt' };
        const labels = { image:'Foto', video:'Video', audio:'Audio', document:'Dokumen', sticker:'Stiker', location:'Lokasi' };
        mediaTag = `<div class="bubble-media-tag"><i class="bi ${icons[msg.message_type] || 'bi-chat'}"></i> ${labels[msg.message_type] || msg.message_type}</div>`;
    }

    row.innerHTML = `
        ${avatarHtml}
        <div class="chat-bubble ${msg.direction === 'out' ? 'bubble-out' : 'bubble-in'}">
            ${mediaTag}
            <div class="bubble-text">${escapeHtml(msg.message_body || '')}</div>
            <div class="bubble-time">${time} ${statusIcon}</div>
        </div>
    `;
    chatMessages.appendChild(row);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `position:fixed;top:20px;right:20px;z-index:9999;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:500;animation:fadeInUp 0.3s ease;max-width:400px;`;
    toast.style.background = type === 'error' ? '#ff4444' : (type === 'success' ? 'var(--accent)' : 'var(--bg-secondary)');
    toast.style.color = type === 'error' || type === 'success' ? '#fff' : 'var(--text-primary)';
    if (type === 'success') toast.style.color = '#000';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 4000);
}

// Realtime polling
let pollInterval = setInterval(pollNewMessages, 3000);

function pollNewMessages() {
    fetch(`${POLL_URL}?after_id=${lastMessageId}`, {
        headers: { 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                // Skip if already rendered
                if (document.querySelector(`[data-msg-id="${msg.id}"]`)) return;
                appendBubble(msg);
                lastMessageId = Math.max(lastMessageId, msg.id);
            });
            if (data.messages.length > 0) scrollToBottom();
        }
    })
    .catch(() => {}); // Silent fail
}

// Enter to send
messageInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    }
});

// Cleanup polling on page leave
window.addEventListener('beforeunload', () => clearInterval(pollInterval));
</script>
@endsection
