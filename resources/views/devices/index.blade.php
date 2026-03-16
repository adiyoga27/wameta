@extends('layouts.app')

@section('title', 'Devices')

@section('actions')
<a href="{{ route('devices.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Device</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-phone-fill" style="color:var(--accent);margin-right:8px;"></i> Daftar Devices</h3>
    </div>
    @if($devices->isEmpty())
        <div class="empty-state">
            <i class="bi bi-phone"></i>
            <h4>Belum ada device</h4>
            <p>Tambahkan device WhatsApp Business pertama Anda</p>
        </div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Phone Number ID</th>
                        <th>WABA ID</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th>Webhook</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($devices as $device)
                    <tr>
                        <td style="font-weight:600;">{{ $device->name }}</td>
                        <td><span class="phone-tag">{{ $device->phone_number_id ?: '-' }}</span></td>
                        <td style="font-size:13px;color:var(--text-muted);">{{ $device->waba_id ?: '-' }}</td>
                        <td>
                            @foreach($device->users as $u)
                                <span class="badge badge-info" style="margin-right:4px;">{{ $u->name }}</span>
                            @endforeach
                            @if($device->users->isEmpty())
                                <span class="badge badge-secondary">Tidak ada</span>
                            @endif
                        </td>
                        <td>
                            @if($device->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            @if($device->webhook_logs_count > 0)
                                <span class="badge badge-success" title="Total {{ $device->webhook_logs_count }} webhook diterima">
                                    <i class="bi bi-check-circle-fill"></i> Terhubung
                                </span>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                                    <i class="bi bi-clock"></i> {{ $device->last_webhook_at ? \Carbon\Carbon::parse($device->last_webhook_at)->diffForHumans() : '-' }}
                                </div>
                                <div style="font-size:11px;color:var(--text-muted);">
                                    {{ $device->webhook_logs_count }} event diterima
                                </div>
                            @else
                                <span class="badge badge-danger">
                                    <i class="bi bi-x-circle-fill"></i> Belum Terhubung
                                </span>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                                    Belum ada webhook masuk
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="{{ route('devices.show', $device) }}" class="btn btn-primary btn-sm" title="Detail"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('devices.edit', $device) }}" class="btn btn-secondary btn-sm"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('Yakin hapus device ini?')">
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

<!-- Webhook Info -->
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3><i class="bi bi-link-45deg" style="color:var(--info);margin-right:8px;"></i> Webhook Information</h3>
    </div>
    <p style="font-size:14px;color:var(--text-secondary);margin-bottom:12px;">Gunakan URL berikut untuk konfigurasi webhook di Meta Developer Console:</p>
    <div style="background:var(--bg-primary);padding:14px 18px;border-radius:8px;border:1px solid var(--border);display:flex;align-items:center;gap:12px;">
        <code style="flex:1;color:var(--accent);font-size:14px;">{{ url('/api/webhook') }}</code>
        <button onclick="navigator.clipboard.writeText('{{ url('/api/webhook') }}')" class="btn btn-secondary btn-sm"><i class="bi bi-clipboard"></i></button>
    </div>
    <p style="font-size:12px;color:var(--text-muted);margin-top:10px;">Setiap device memiliki Verify Token unik yang ditampilkan saat edit device.</p>
</div>
@endsection
