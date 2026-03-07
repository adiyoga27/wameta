@extends('layouts.app')

@section('title', 'WhatsApp Meta Pricing')

@section('content')
<x-tutorial title="Panduan Meta Pricing">
    <p>Halaman ini khusus untuk mengatur nilai konversi atau nominal pemotongan Saldo Device setiap kali pesan Meta dikirimkan.</p>
    <ul>
        <li><strong>Meta Pricing Categories:</strong> Ada 4 kategori dasar dari Meta API. Setiap pesan yang berhasil membuka jendela 24 jam baru akan ditagih Meta berdasarkan kategori ini.</li>
        <li>Isikan nominal IDR sesuai harga dari supplier WhatsApp Business Provider Anda, ditambah margin (jika ada).</li>
        <li>Sistem akan otomatis memotong (mendeduct) balance Device setiap kali Webhook Meta mengirim notifikasi <code>pricing.billable = true</code>.</li>
    </ul>
    <p>Kosongkan atau set 0 jika Anda tidak ingin membebankan biaya untuk kategori tertentu.</p>
</x-tutorial>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3><i class="bi bi-tag-fill" style="margin-right: 8px; color: var(--info);"></i> Konfigurasi Harga API Meta (IDR)</h3>
    </div>
    
    <div class="card-body">
        <form action="{{ route('settings.store') }}" method="POST">
            @csrf

            <div class="alert alert-info">
                Masukkan nilai per pemotongan dalam mata uang Rupiah (IDR). Pemotongan saldonya dilakukan berdasarkan webhook log real-time dari Meta API.
            </div>

            @foreach($categories as $key => $label)
            <div class="form-group">
                <label for="meta_pricing_{{ $key }}" class="form-label" style="font-weight: 600;">
                    Kategori <span style="text-transform: capitalize;">{{ $label }}</span>
                </label>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-weight: 600; color: var(--text-muted);">Rp</span>
                    <input type="number" 
                           id="meta_pricing_{{ $key }}" 
                           name="meta_pricing_{{ $key }}" 
                           class="form-control" 
                           value="{{ $settings['meta_pricing_'.$key] ?? 0 }}" 
                           min="0" 
                           required>
                </div>
                <div class="form-hint">
                    Biaya saat ini: IDR {{ number_format($settings['meta_pricing_'.$key] ?? 0, 0, ',', '.') }} per message delivery.
                </div>
            </div>
            <hr style="border-color: var(--border); margin: 15px 0;">
            @endforeach

            <div style="text-align: right; margin-top: 25px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Harga
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
