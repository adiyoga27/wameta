@extends('layouts.app')

@section('title', 'Edit Device')

@section('content')
<div class="card" style="max-width:700px;">
    <div class="card-header">
        <h3><i class="bi bi-pencil-fill" style="color:var(--accent);margin-right:8px;"></i> Edit Device</h3>
    </div>

    <form method="POST" action="{{ route('devices.update', $device) }}">
        @csrf @method('PUT')
        <div class="form-group">
            <label class="form-label">Nama Device *</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $device->name) }}" required>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">WABA Business Account ID</label>
                <input type="text" name="waba_id" class="form-control" value="{{ old('waba_id', $device->waba_id) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number ID *</label>
                <input type="text" name="phone_number_id" class="form-control" value="{{ old('phone_number_id', $device->phone_number_id) }}">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Access Token (Meta) *</label>
            <textarea name="access_token" class="form-control" rows="3">{{ old('access_token', $device->access_token) }}</textarea>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">App ID</label>
                <input type="text" name="app_id" class="form-control" value="{{ old('app_id', $device->app_id) }}">
            </div>
            <div class="form-group">
                <label class="form-label">App Secret</label>
                <input type="text" name="app_secret" class="form-control" value="{{ old('app_secret', $device->app_secret) }}">
            </div>
        </div>

        <!-- Webhook Verify Token (readonly) -->
        <div class="form-group">
            <label class="form-label">Webhook Verify Token</label>
            <div style="background:var(--bg-primary);padding:12px 14px;border-radius:8px;border:1px solid var(--border);display:flex;align-items:center;gap:10px;">
                <code style="flex:1;color:var(--accent);font-size:13px;">{{ $device->webhook_verify_token }}</code>
                <button type="button" onclick="navigator.clipboard.writeText('{{ $device->webhook_verify_token }}')" class="btn btn-secondary btn-sm"><i class="bi bi-clipboard"></i></button>
            </div>
            <div class="form-hint">Gunakan token ini saat konfigurasi webhook di Meta Developer Console</div>
        </div>

        <hr style="border-color:var(--border);margin:30px 0;">

        <h4 style="margin-bottom:15px;"><i class="bi bi-tag-fill" style="color:var(--info);margin-right:8px;"></i> Konfigurasi Harga API Meta (IDR)</h4>
        <div class="form-hint" style="margin-bottom:20px;">Masukkan tarif pemotongan saldo (billing) spesifik untuk perangkat ini. Nilai kosong akan dianggap 0 (gratis).</div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Kategori Marketing</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" step="0.01" min="0" name="pricing_marketing" class="form-control" value="{{ old('pricing_marketing', $device->pricing_marketing) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori Utility</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" step="0.01" min="0" name="pricing_utility" class="form-control" value="{{ old('pricing_utility', $device->pricing_utility) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori Authentication</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" step="0.01" min="0" name="pricing_authentication" class="form-control" value="{{ old('pricing_authentication', $device->pricing_authentication) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori Service</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" step="0.01" min="0" name="pricing_service" class="form-control" value="{{ old('pricing_service', $device->pricing_service) }}">
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Device</button>
            <a href="{{ route('devices.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
