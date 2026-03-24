@if($conversations->isEmpty())
    <div class="empty-state" style="padding:40px 20px;">
        <i class="bi bi-chat-dots" style="font-size:36px;"></i>
        <h4 style="font-size:14px;">Belum ada percakapan</h4>
        <p style="font-size:12px;">Pencarian tidak menemukan hasil</p>
    </div>
@else
    <div class="conversation-list">
        @foreach($conversations as $conv)
        <a href="{{ route('messages.show', ['deviceId' => $deviceId, 'contactNumber' => $conv->contact_number]) }}{{ !empty($search) ? '?search=' . urlencode($search) : '' }}" 
           class="conversation-item {{ isset($activeContact) && $activeContact === $conv->contact_number ? 'active' : '' }}">
            <div class="conv-avatar">{{ strtoupper(substr($conv->contact_name ?? $conv->contact_number, 0, 1)) }}</div>
            <div class="conv-info">
                <div class="conv-name" style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;">{{ $conv->contact_name ?? $conv->contact_number }}</span>
                </div>
                @if(isset($conv->labels) && $conv->labels->count() > 0)
                    <div class="conv-labels-badges" style="display: flex; gap: 3px; flex-wrap: wrap; margin: 2px 0 4px 0;">
                    @foreach($conv->labels as $l)
                        <span style="font-size: 8px; font-weight: 700; padding: 1px 4px; border-radius: 3px; background-color: {{ $l->chatLabel->color_hex }}; color: white; white-space: nowrap; text-transform: uppercase;">
                            {{ $l->chatLabel->name }}
                        </span>
                    @endforeach
                    </div>
                @endif
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
