@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="card" style="max-width:700px;">
    <div class="card-header">
        <h3><i class="bi bi-pencil-fill" style="color:var(--accent);margin-right:8px;"></i> Edit User: {{ $user->name }}</h3>
    </div>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nama *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Password <span style="color:var(--text-muted);">(kosongkan jika tidak diubah)</span></label>
                <input type="password" name="password" class="form-control" placeholder="Min 6 karakter">
            </div>
            <div class="form-group">
                <label class="form-label">Role *</label>
                <select name="role" class="form-control" required>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="superadmin" {{ old('role', $user->role) === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                </select>
            </div>
        </div>

        @if($devices->isNotEmpty())
        <div class="form-group">
            <label class="form-label">Assign Devices</label>
            <div class="form-hint" style="margin-bottom:10px;">Pilih device/credential yang boleh diakses oleh user ini</div>
            <div class="checkbox-list">
                @foreach($devices as $device)
                <label class="checkbox-item {{ in_array($device->id, $assignedDevices) ? 'checked' : '' }}">
                    <input type="checkbox" name="devices[]" value="{{ $device->id }}"
                        onchange="this.closest('.checkbox-item').classList.toggle('checked', this.checked)"
                        {{ in_array($device->id, old('devices', $assignedDevices)) ? 'checked' : '' }}>
                    <div>
                        <div style="font-weight:600;font-size:13px;">{{ $device->name }}</div>
                        <div style="font-size:11px;color:var(--text-muted);">{{ $device->phone_number_id ?: 'Belum set' }}</div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update User</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
