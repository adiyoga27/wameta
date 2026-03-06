@extends('layouts.app')

@section('title', 'Detail Broadcast')

@section('content')
<!-- Summary Card -->
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-megaphone-fill"></i></div>
        <div class="stat-value">{{ $broadcast->total }}</div>
        <div class="stat-label">Total Kontak</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-send-check-fill"></i></div>
        <div class="stat-value">{{ $broadcast->sent }}</div>
        <div class="stat-label">Terkirim</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-check2-all"></i></div>
        <div class="stat-value">{{ $broadcast->delivered }}</div>
        <div class="stat-label">Diterima</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-eye-fill"></i></div>
        <div class="stat-value">{{ $broadcast->read }}</div>
        <div class="stat-label">Dibaca</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
        <div class="stat-value">{{ $broadcast->failed }}</div>
        <div class="stat-label">Gagal</div>
    </div>
</div>

<!-- Broadcast Info -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3>
            <i class="bi bi-megaphone-fill" style="color:var(--accent);margin-right:8px;"></i>
            {{ $broadcast->name }}
        </h3>
        <div style="display:flex;gap:8px;align-items:center;">
            @switch($broadcast->status)
                @case('completed') <span class="badge badge-success">Selesai</span> @break
                @case('sending') <span class="badge badge-info">Mengirim...</span> @break
                @case('draft') <span class="badge badge-secondary">Draft</span> @break
                @default <span class="badge badge-danger">Gagal</span>
            @endswitch

            @if($broadcast->status === 'draft' || ($broadcast->status === 'completed' && $broadcast->broadcastContacts()->where('status', 'pending')->count() > 0))
                <form method="POST" action="{{ route('broadcasts.send', $broadcast) }}" onsubmit="return confirm('Yakin kirim broadcast ini? Pesan akan dikirim ke semua kontak yang pending.')">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send-fill"></i> Kirim Sekarang</button>
                </form>
            @endif
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:14px;">
        <div>
            <span style="color:var(--text-muted);">Device:</span>
            <span style="font-weight:600;">{{ $broadcast->device->name ?? '-' }}</span>
        </div>
        <div>
            <span style="color:var(--text-muted);">Template:</span>
            <span class="badge badge-purple">{{ $broadcast->messageTemplate->name ?? '-' }}</span>
        </div>
        <div>
            <span style="color:var(--text-muted);">Dibuat oleh:</span>
            <span>{{ $broadcast->user->name ?? '-' }}</span>
        </div>
        <div>
            <span style="color:var(--text-muted);">Tanggal:</span>
            <span>{{ $broadcast->created_at->format('d M Y H:i') }}</span>
        </div>
    </div>
</div>

<!-- Contact Delivery Status -->
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-person-lines-fill" style="color:var(--info);margin-right:8px;"></i> Status Pengiriman per Kontak</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Nama</th><th>Nomor</th><th>Status</th><th>Pesan Error</th><th>Waktu</th></tr>
            </thead>
            <tbody>
                @foreach($broadcast->broadcastContacts as $bc)
                <tr>
                    <td style="font-weight:600;">{{ $bc->contact->name ?? 'Tanpa Nama' }}</td>
                    <td><span class="phone-tag">{{ $bc->contact->phone }}</span></td>
                    <td>
                        @switch($bc->status)
                            @case('sent') <span class="badge badge-success"><i class="bi bi-check"></i> Terkirim</span> @break
                            @case('delivered') <span class="badge badge-info"><i class="bi bi-check2-all"></i> Diterima</span> @break
                            @case('read') <span class="badge badge-info" style="background:rgba(83,189,235,0.12);color:#53bdeb;"><i class="bi bi-eye"></i> Dibaca</span> @break
                            @case('failed') <span class="badge badge-danger"><i class="bi bi-x-circle"></i> Gagal</span> @break
                            @default <span class="badge badge-secondary"><i class="bi bi-clock"></i> Pending</span>
                        @endswitch
                    </td>
                    <td style="font-size:13px;color:var(--danger);">{{ $bc->error_message ?? '-' }}</td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $bc->updated_at?->format('H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
