@extends('layouts.app')

@section('title', 'Webhook Log Detail')

@section('actions')
<a href="{{ route('webhook-logs.index') }}" class="btn btn-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Kembali
</a>
@endsection

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-calendar3"></i></div>
        <div class="stat-label">Waktu</div>
        <div style="font-size:14px;font-weight:600;margin-top:4px;">{{ $webhookLog->created_at->format('d M Y H:i:s') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-phone"></i></div>
        <div class="stat-label">Device</div>
        <div style="font-size:14px;font-weight:600;margin-top:4px;">{{ $webhookLog->device->name ?? '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-tag"></i></div>
        <div class="stat-label">Event Type</div>
        <div style="margin-top:6px;">
            @foreach(explode(',', $webhookLog->event_type) as $type)
                @switch(trim($type))
                    @case('messages')
                        <span class="badge badge-info">messages</span>
                        @break
                    @case('statuses')
                        <span class="badge badge-success">statuses</span>
                        @break
                    @case('errors')
                        <span class="badge badge-danger">errors</span>
                        @break
                    @default
                        <span class="badge badge-secondary">{{ trim($type) }}</span>
                @endswitch
            @endforeach
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon {{ $webhookLog->processed ? 'green' : ($webhookLog->error_message ? 'red' : 'orange') }}">
            <i class="bi bi-{{ $webhookLog->processed ? 'check-circle' : ($webhookLog->error_message ? 'x-circle' : 'hourglass') }}"></i>
        </div>
        <div class="stat-label">Status</div>
        <div style="margin-top:6px;">
            @if($webhookLog->processed)
                <span class="badge badge-success">Processed</span>
            @elseif($webhookLog->error_message)
                <span class="badge badge-danger">Error</span>
            @else
                <span class="badge badge-warning">Pending</span>
            @endif
        </div>
    </div>
</div>

@if($webhookLog->error_message)
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <strong>Error:</strong> {{ $webhookLog->error_message }}
    </div>
</div>
@endif

@if($webhookLog->phone_number_id)
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3><i class="bi bi-info-circle" style="color:var(--info);margin-right:8px;"></i> Metadata</h3>
    </div>
    <table>
        <tbody>
            <tr>
                <td style="color:var(--text-muted);width:200px;">Phone Number ID</td>
                <td><code style="color:var(--accent);font-size:13px;">{{ $webhookLog->phone_number_id }}</code></td>
            </tr>
            <tr>
                <td style="color:var(--text-muted);">Webhook Log ID</td>
                <td><code style="color:var(--accent);font-size:13px;">#{{ $webhookLog->id }}</code></td>
            </tr>
        </tbody>
    </table>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-code-slash" style="color:var(--warning);margin-right:8px;"></i> Raw Payload</h3>
        <button onclick="copyPayload()" class="btn btn-secondary btn-sm">
            <i class="bi bi-clipboard"></i> Copy JSON
        </button>
    </div>
    <pre id="jsonPayload" style="
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 20px;
        overflow-x: auto;
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 13px;
        line-height: 1.6;
        color: var(--text-secondary);
        max-height: 600px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-word;
    ">{{ json_encode($webhookLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
</div>
@endsection

@section('scripts')
<script>
function copyPayload() {
    const text = document.getElementById('jsonPayload').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
        setTimeout(() => btn.innerHTML = originalHTML, 2000);
    });
}
</script>
@endsection
