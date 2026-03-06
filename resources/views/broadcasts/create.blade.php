@extends('layouts.app')

@section('title', 'Buat Broadcast')

@section('content')
<div class="card" style="max-width:800px;">
    <div class="card-header">
        <h3><i class="bi bi-megaphone-fill" style="color:var(--accent);margin-right:8px;"></i> Buat Broadcast Baru</h3>
    </div>

    <form method="POST" action="{{ route('broadcasts.store') }}" id="broadcastForm">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Device *</label>
                <select name="device_id" class="form-control" required>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}" {{ $deviceId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Broadcast *</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Promo Maret 2026" value="{{ old('name') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Template Pesan (Approved) *</label>
            @if($templates->isEmpty())
                <div class="alert alert-warning" style="margin-bottom:0;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Belum ada template yang diapprove. <a href="{{ route('templates.create', ['device_id' => $deviceId]) }}">Buat template dulu</a>.
                </div>
            @else
                <select name="message_template_id" class="form-control" required>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}" {{ old('message_template_id') == $tpl->id ? 'selected' : '' }}>
                            {{ $tpl->name }} ({{ $tpl->category }})
                        </option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="form-group">
            <label class="form-label">Pilih Kontak *</label>
            <div class="form-hint" style="margin-bottom:10px;">Centang kontak yang akan menerima broadcast. <strong id="selectedCount">0</strong> kontak terpilih.</div>

            @if($contacts->isEmpty())
                <div class="alert alert-warning" style="margin-bottom:0;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Belum ada kontak. <a href="{{ route('contacts.index') }}">Import kontak dulu</a>.
                </div>
            @else
                <div style="margin-bottom:12px;display:flex;gap:8px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="selectAll()"><i class="bi bi-check2-all"></i> Pilih Semua</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()"><i class="bi bi-x-lg"></i> Hapus Semua</button>
                </div>
                <div style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px;">
                    @foreach($contacts as $contact)
                    <label class="checkbox-item" style="margin-bottom:4px;">
                        <input type="checkbox" name="contact_ids[]" value="{{ $contact->id }}" onchange="updateCount()">
                        <div>
                            <div style="font-weight:600;font-size:13px;">{{ $contact->name ?: 'Tanpa Nama' }}</div>
                            <div style="font-size:12px;color:var(--text-muted);">{{ $contact->phone }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            @endif
        </div>

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-primary" {{ $templates->isEmpty() || $contacts->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-megaphone-fill"></i> Buat Broadcast
            </button>
            <a href="{{ route('broadcasts.index', ['device_id' => $deviceId]) }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function updateCount() {
    const checked = document.querySelectorAll('input[name="contact_ids[]"]:checked').length;
    document.getElementById('selectedCount').textContent = checked;
}
function selectAll() {
    document.querySelectorAll('input[name="contact_ids[]"]').forEach(cb => { cb.checked = true; cb.closest('.checkbox-item').classList.add('checked'); });
    updateCount();
}
function deselectAll() {
    document.querySelectorAll('input[name="contact_ids[]"]').forEach(cb => { cb.checked = false; cb.closest('.checkbox-item').classList.remove('checked'); });
    updateCount();
}
</script>
@endsection
