@extends('layouts.app')

@section('title', 'Pembayaran Selesai')

@section('content')
<div class="card" style="max-width:600px;margin:0 auto;text-align:center;">
    <div style="margin:30px 0;">
        @if($topup && ($topup->status === 'settlement' || $topup->status === 'capture'))
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(37,211,102,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="bi bi-check-circle-fill" style="font-size:40px;color:var(--accent);"></i>
            </div>
            <h2 style="margin-bottom:8px;color:var(--accent);">Pembayaran Berhasil!</h2>
            <p style="color:var(--text-muted);">Saldo Anda telah ditambahkan.</p>
            <div style="font-size:28px;font-weight:800;color:var(--accent);margin:16px 0;">Rp {{ number_format($topup->amount, 0, ',', '.') }}</div>
        @elseif($topup && $topup->status === 'pending')
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,193,7,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="bi bi-clock-fill" style="font-size:40px;color:var(--warning);"></i>
            </div>
            <h2 style="margin-bottom:8px;color:var(--warning);">Menunggu Pembayaran</h2>
            <p style="color:var(--text-muted);">Silakan selesaikan pembayaran Anda. Saldo akan otomatis ditambahkan setelah pembayaran dikonfirmasi.</p>
            <div style="font-size:28px;font-weight:800;color:var(--warning);margin:16px 0;">Rp {{ number_format($topup->amount, 0, ',', '.') }}</div>
            <div style="font-size:12px;color:var(--text-muted);">Order ID: {{ $topup->order_id }}</div>
        @else
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(220,53,69,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="bi bi-x-circle-fill" style="font-size:40px;color:var(--danger);"></i>
            </div>
            <h2 style="margin-bottom:8px;color:var(--danger);">Pembayaran Gagal</h2>
            <p style="color:var(--text-muted);">Transaksi tidak ditemukan atau pembayaran gagal.</p>
        @endif
    </div>

    <a href="{{ route('topups.index', ['device_id' => $topup?->device_id]) }}" class="btn btn-primary" style="width:100%;">
        <i class="bi bi-arrow-left"></i> Kembali ke Top Up
    </a>
    <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="width:100%;margin-top:8px;">
        Dashboard
    </a>
</div>
@endsection
