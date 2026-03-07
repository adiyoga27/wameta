@extends('layouts.app')

@section('title', 'Webhook Logs')

@section('actions')
<form method="GET" action="{{ route('webhook-logs.index') }}" style="display:flex;gap:8px;align-items:center;">
    <select name="device_id" class="form-control" style="width:180px;padding:8px 12px;" onchange="this.form.submit()">
        <option value="">Semua Device</option>
        @foreach($devices as $d)
            <option value="{{ $d->id }}" {{ $deviceId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
        @endforeach
    </select>
    <select name="event_type" class="form-control" style="width:160px;padding:8px 12px;" onchange="this.form.submit()">
        <option value="">Semua Event</option>
        <option value="messages" {{ $eventType === 'messages' ? 'selected' : '' }}>Messages</option>
        <option value="statuses" {{ $eventType === 'statuses' ? 'selected' : '' }}>Statuses</option>
        <option value="errors" {{ $eventType === 'errors' ? 'selected' : '' }}>Errors</option>
        <option value="unknown" {{ $eventType === 'unknown' ? 'selected' : '' }}>Unknown</option>
    </select>
</form>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-journal-text" style="color:var(--purple);margin-right:8px;"></i> Webhook Logs</h3>
        <span style="font-size:13px;color:var(--text-muted);">{{ $logs->total() }} log tercatat</span>
    </div>
    @if($logs->isEmpty())
        <div class="empty-state">
            <i class="bi bi-journal-x"></i>
            <h4>Belum ada webhook log</h4>
            <p>Log akan muncul di sini ketika ada webhook masuk dari Meta</p>
        </div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Device</th>
                        <th>Event Type</th>
                        <th>Phone Number ID</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                            {{ $log->created_at->format('d M Y H:i:s') }}
                        </td>
                        <td>
                            @if($log->device)
                                <span style="font-weight:600;font-size:13px;">{{ $log->device->name }}</span>
                            @else
                                <span style="color:var(--text-muted);font-size:13px;">—</span>
                            @endif
                        </td>
                        <td>
                            @foreach(explode(',', $log->event_type) as $type)
                                @switch(trim($type))
                                    @case('messages')
                                        <span class="badge badge-info"><i class="bi bi-chat-text"></i> messages</span>
                                        @break
                                    @case('statuses')
                                        <span class="badge badge-success"><i class="bi bi-check2-all"></i> statuses</span>
                                        @break
                                    @case('errors')
                                        <span class="badge badge-danger"><i class="bi bi-x-circle"></i> errors</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">{{ trim($type) }}</span>
                                @endswitch
                            @endforeach
                        </td>
                        <td>
                            <code style="font-size:12px;color:var(--text-secondary);">{{ $log->phone_number_id ?? '—' }}</code>
                        </td>
                        <td>
                            @if($log->processed)
                                <span class="badge badge-success"><i class="bi bi-check"></i> OK</span>
                            @elseif($log->error_message)
                                <span class="badge badge-danger" title="{{ $log->error_message }}"><i class="bi bi-x"></i> Error</span>
                            @else
                                <span class="badge badge-warning"><i class="bi bi-hourglass"></i> Pending</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('webhook-logs.show', $log) }}" class="btn btn-secondary btn-sm">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">
            {{ $logs->appends(['device_id' => $deviceId, 'event_type' => $eventType])->links('pagination::simple-bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
