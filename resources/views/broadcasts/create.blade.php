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
                <div style="margin-bottom:12px;display:flex;gap:8px;align-items:center;background:var(--bg-secondary);padding:10px;border-radius:8px;">
                    <div style="flex:1;">
                        <input type="text" id="contactSearch" class="form-control" placeholder="Cari nama/nomor..." onkeyup="filterContacts()" style="padding:6px 10px;font-size:13px;">
                    </div>
                    <div>
                        <select id="categoryFilter" class="form-control" onchange="filterContacts()" style="padding:6px 10px;font-size:13px;width:180px;">
                            <option value="all">Semua Kategori</option>
                            <option value="none">Tanpa Kategori</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllVisible()"><i class="bi bi-check2-all"></i> Pilih Tampil</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()"><i class="bi bi-x-lg"></i> Hapus Semua</button>
                </div>
                <div id="contactList" style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px;">
                    @foreach($contacts as $contact)
                    <label class="checkbox-item contact-row" data-name="{{ strtolower($contact->name ?? '') }}" data-phone="{{ $contact->phone }}" data-category="{{ $contact->category_id ?? 'none' }}" style="margin-bottom:4px;display:flex;">
                        <input type="checkbox" name="contact_ids[]" value="{{ $contact->id }}" class="contact-checkbox" onchange="updateCount()">
                        <div style="flex:1;">
                            <div style="font-weight:600;font-size:13px;display:flex;justify-content:space-between;align-items:center;">
                                <span>{{ $contact->name ?: 'Tanpa Nama' }}</span>
                                @if($contact->category)
                                <span class="badge" style="font-size:9px;background-color:{{ $contact->category->color }}20;color:{{ $contact->category->color }};border:1px solid {{ $contact->category->color }}50;">{{ $contact->category->name }}</span>
                                @endif
                            </div>
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
    const checked = document.querySelectorAll('.contact-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = checked;
}

function filterContacts() {
    const search = document.getElementById('contactSearch').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const rows = document.querySelectorAll('.contact-row');

    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const phone = row.getAttribute('data-phone');
        const rowCategory = row.getAttribute('data-category');

        const matchesSearch = name.includes(search) || phone.includes(search);
        const matchesCategory = category === 'all' || rowCategory === category;

        if (matchesSearch && matchesCategory) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
            // Optional: uncheck if hidden
            // row.querySelector('.contact-checkbox').checked = false;
        }
    });
    // updateCount();
}

function selectAllVisible() {
    document.querySelectorAll('.contact-row').forEach(row => {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.contact-checkbox');
            cb.checked = true;
            row.classList.add('checked');
        }
    });
    updateCount();
}

function deselectAll() {
    document.querySelectorAll('.contact-checkbox').forEach(cb => {
        cb.checked = false;
        cb.closest('.checkbox-item').classList.remove('checked');
    });
    updateCount();
}

// Add visual cue for checked state
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('contact-checkbox')) {
        if (e.target.checked) {
            e.target.closest('.checkbox-item').classList.add('checked');
        } else {
            e.target.closest('.checkbox-item').classList.remove('checked');
        }
    }
});
</script>
@endsection
