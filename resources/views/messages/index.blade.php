@extends('layouts.app')

@section('title', 'Pesan Masuk')

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
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-chat-dots-fill" style="color:var(--accent);margin-right:8px;"></i> Pesan Masuk</h3>
    </div>
    @if($messages->isEmpty())
        <div class="empty-state">
            <i class="bi bi-chat-dots"></i>
            <h4>Belum ada pesan masuk</h4>
            <p>Pesan akan muncul di sini ketika ada pesan masuk melalui webhook</p>
        </div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Dari</th><th>Tipe</th><th>Pesan</th><th>Waktu</th></tr>
                </thead>
                <tbody>
                    @foreach($messages as $msg)
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $msg->from_name ?? 'Unknown' }}</div>
                            <div style="font-size:12px;color:var(--text-muted);">{{ $msg->from_number }}</div>
                        </td>
                        <td>
                            @switch($msg->message_type)
                                @case('text') <span class="badge badge-info"><i class="bi bi-chat-text"></i> Text</span> @break
                                @case('image') <span class="badge badge-success"><i class="bi bi-image"></i> Image</span> @break
                                @case('video') <span class="badge badge-purple"><i class="bi bi-camera-video"></i> Video</span> @break
                                @case('audio') <span class="badge badge-warning"><i class="bi bi-mic"></i> Audio</span> @break
                                @case('document') <span class="badge badge-secondary"><i class="bi bi-file-earmark"></i> Doc</span> @break
                                @case('location') <span class="badge badge-danger"><i class="bi bi-geo-alt"></i> Location</span> @break
                                @default <span class="badge badge-secondary">{{ $msg->message_type }}</span>
                            @endswitch
                        </td>
                        <td style="max-width:350px;">
                            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $msg->message_body }}</div>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                            {{ $msg->wa_timestamp?->format('d M Y H:i') ?? $msg->created_at->format('d M Y H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">
            {{ $messages->appends(['device_id' => $deviceId])->links('pagination::simple-bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
