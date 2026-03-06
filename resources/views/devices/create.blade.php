@extends('layouts.app')

@section('title', 'Tambah Device')

@section('content')
<div class="card" style="max-width:700px;">
    <div class="card-header">
        <h3><i class="bi bi-plus-circle-fill" style="color:var(--accent);margin-right:8px;"></i> Tambah Device Baru</h3>
    </div>

    <form method="POST" action="{{ route('devices.store') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Nama Device *</label>
            <input type="text" name="name" class="form-control" placeholder="Contoh: CS Utama, Marketing, dll" value="{{ old('name') }}" required>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">WABA Business Account ID</label>
                <input type="text" name="waba_id" class="form-control" placeholder="e.g. 123456789..." value="{{ old('waba_id') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number ID *</label>
                <input type="text" name="phone_number_id" class="form-control" placeholder="e.g. 123456789..." value="{{ old('phone_number_id') }}">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Access Token (Meta) *</label>
            <textarea name="access_token" class="form-control" rows="3" placeholder="Paste access token dari Meta Developer Console">{{ old('access_token') }}</textarea>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">App ID</label>
                <input type="text" name="app_id" class="form-control" placeholder="Facebook App ID" value="{{ old('app_id') }}">
            </div>
            <div class="form-group">
                <label class="form-label">App Secret</label>
                <input type="text" name="app_secret" class="form-control" placeholder="Facebook App Secret" value="{{ old('app_secret') }}">
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Device</button>
            <a href="{{ route('devices.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
