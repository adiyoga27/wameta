@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<x-tutorial title="Selamat Datang di Dashboard WA Meta">
    <p>Ini adalah halaman utama laporan performa pengiriman pesan WhatsApp Anda. Di sini Anda bisa memantau:</p>
    <ul>
        <li><strong>Ringkasan Metrik:</strong> Jumlah perangkat, template Meta yang aktif, sesi broadcast, daftar kontak yang tersimpan, dan pesan masuk.</li>
        <li><strong>Status Pengiriman:</strong> Pantau secara real-time berapa banyak pesan broadcast Anda yang berhasil <code>Terkirim</code>, <code>Diterima</code> (masuk ke HP tujuan), <code>Dibaca</code> (centang biru), atau justru <code>Gagal</code>.</li>
        <li><strong>Aktivitas Terbaru:</strong> Temukan daftar broadcast terakhir yang Anda luncurkan dan pesan balasan terbaru dari pelanggan di bagian bawah halaman.</li>
    </ul>
</x-tutorial>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-phone-fill"></i></div>
        <div class="stat-value">{{ $stats['total_devices'] }}</div>
        <div class="stat-label">Devices</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div class="stat-value">{{ $stats['total_templates'] }}</div>
        <div class="stat-label">Templates</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-value">{{ $stats['approved_templates'] }}</div>
        <div class="stat-label">Approved</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-megaphone-fill"></i></div>
        <div class="stat-value">{{ $stats['total_broadcasts'] }}</div>
        <div class="stat-label">Broadcasts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-person-lines-fill"></i></div>
        <div class="stat-value">{{ $stats['total_contacts'] }}</div>
        <div class="stat-label">Kontak</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-chat-dots-fill"></i></div>
        <div class="stat-value">{{ $stats['total_messages'] }}</div>
        <div class="stat-label">Pesan Masuk</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Recent Broadcasts -->
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-megaphone-fill" style="color:var(--accent);margin-right:8px;"></i> Broadcast Terbaru</h3>
            <a href="{{ route('broadcasts.index') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        @if($recentBroadcasts->isEmpty())
            <div class="empty-state">
                <i class="bi bi-megaphone"></i>
                <h4>Belum ada broadcast</h4>
            </div>
        @else
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Nama</th><th>Terkirim</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($recentBroadcasts as $bc)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $bc->name }}</div>
                                <div style="font-size:12px;color:var(--text-muted);">{{ $bc->device->name ?? '-' }}</div>
                            </td>
                            <td>{{ $bc->sent }}/{{ $bc->total }}</td>
                            <td>
                                @if($bc->status === 'completed')
                                    <span class="badge badge-success">Selesai</span>
                                @elseif($bc->status === 'sending')
                                    <span class="badge badge-info">Mengirim</span>
                                @elseif($bc->status === 'draft')
                                    <span class="badge badge-secondary">Draft</span>
                                @else
                                    <span class="badge badge-danger">Gagal</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Recent Messages -->
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-chat-dots-fill" style="color:var(--info);margin-right:8px;"></i> Pesan Terbaru</h3>
            <a href="{{ route('messages.index') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        @if($recentMessages->isEmpty())
            <div class="empty-state">
                <i class="bi bi-chat-dots"></i>
                <h4>Belum ada pesan masuk</h4>
            </div>
        @else
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Dari</th><th>Pesan</th><th>Waktu</th></tr></thead>
                    <tbody>
                        @foreach($recentMessages as $msg)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $msg->from_name ?? $msg->from_number }}</div>
                                <div style="font-size:12px;color:var(--text-muted);">{{ $msg->from_number }}</div>
                            </td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $msg->message_body }}</td>
                            <td style="font-size:12px;color:var(--text-muted);">{{ $msg->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Quick Stats Summary -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3><i class="bi bi-bar-chart-fill" style="color:var(--accent);margin-right:8px;"></i> Ringkasan Pengiriman</h3>
    </div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-send-check-fill"></i></div>
            <div class="stat-value">{{ $stats['total_sent'] }}</div>
            <div class="stat-label">Total Terkirim</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-check2-all"></i></div>
            <div class="stat-value">{{ $stats['total_delivered'] }}</div>
            <div class="stat-label">Total Diterima</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-value">{{ $stats['total_failed'] }}</div>
            <div class="stat-label">Total Gagal</div>
        </div>
    </div>
</div>
@endsection
