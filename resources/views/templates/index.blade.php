@extends('layouts.app')

@section('title', 'Message Templates')

@section('actions')
<div style="display:flex;gap:10px;align-items:center;">
    <form method="GET" action="{{ route('templates.index') }}" style="display:flex;gap:8px;align-items:center;">
        <select name="device_id" class="form-control" style="width:200px;padding:8px 12px;" onchange="this.form.submit()">
            @foreach($devices as $d)
                <option value="{{ $d->id }}" {{ $deviceId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
            @endforeach
        </select>
    </form>
    @if($deviceId)
    <form method="POST" action="{{ route('templates.syncAll') }}">
        @csrf
        <input type="hidden" name="device_id" value="{{ $deviceId }}">
        <button type="submit" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-repeat"></i> Sync Semua dari Meta</button>
    </form>
    @endif
    <a href="{{ route('templates.create', ['device_id' => $deviceId]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Buat Template</a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-file-earmark-text-fill" style="color:var(--accent);margin-right:8px;"></i> Daftar Message Templates</h3>
    </div>

    @if(session('error_json'))
    <div style="padding:0 20px;">
        <details style="margin-bottom:12px;">
            <summary style="cursor:pointer;font-size:12px;color:var(--accent);font-weight:600;">
                <i class="bi bi-code-slash"></i> Lihat Response JSON Lengkap dari Meta API
            </summary>
            <pre style="background:var(--bg-primary);border:1px solid var(--border);border-radius:8px;padding:12px;font-size:11px;overflow-x:auto;margin-top:8px;color:var(--text-secondary);max-height:400px;overflow-y:auto;">{{ session('error_json') }}</pre>
        </details>
    </div>
    @endif

    @if($templates->isEmpty())
        <div class="empty-state">
            <i class="bi bi-file-earmark-text"></i>
            <h4>Belum ada template</h4>
            <p>Buat template pesan pertama untuk diajukan ke Meta</p>
        </div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Bahasa</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $tpl)
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $tpl->name }}</div>
                            <div style="font-size:12px;color:var(--text-muted);max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $tpl->body }}</div>
                        </td>
                        <td><span class="badge badge-purple">{{ $tpl->category }}</span></td>
                        <td>{{ $tpl->language }}</td>
                        <td>
                            @switch($tpl->status)
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
                                    <span class="badge badge-secondary">{{ $tpl->status }}</span>
                            @endswitch
                        </td>
                        <td>
                            @if($tpl->rejected_reason)
                                @php
                                    $shortReason = Str::before($tpl->rejected_reason, "\n\n--- Full API Response ---");
                                    $hasJson = Str::contains($tpl->rejected_reason, '--- Full API Response ---');
                                @endphp
                                <span style="color:var(--danger);font-size:13px;">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    {{ Str::limit($shortReason, 50) }}
                                </span>
                                @if($hasJson)
                                <details style="margin-top:4px;">
                                    <summary style="cursor:pointer;font-size:10px;color:var(--accent);">Lihat detail JSON</summary>
                                    <pre style="font-size:10px;background:var(--bg-primary);padding:8px;border-radius:6px;max-height:200px;overflow:auto;margin-top:4px;white-space:pre-wrap;">{{ Str::after($tpl->rejected_reason, "--- Full API Response ---\n") }}</pre>
                                </details>
                                @endif
                            @else
                                <span style="color:var(--text-muted);font-size:13px;">-</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <form method="POST" action="{{ route('templates.sync', $tpl->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Sync status dari Meta"><i class="bi bi-arrow-repeat"></i></button>
                                </form>
                                <form method="POST" action="{{ route('templates.destroy', $tpl->id) }}" onsubmit="return confirm('Yakin hapus template ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
