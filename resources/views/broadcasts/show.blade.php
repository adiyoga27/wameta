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

            @php
                $pendingOrFailed = $broadcast->broadcastContacts()->whereIn('status', ['pending', 'failed'])->count();
            @endphp
            @if(in_array($broadcast->status, ['draft', 'completed', 'failed']) && $pendingOrFailed > 0)
                <form method="POST" action="{{ route('broadcasts.send', $broadcast) }}" onsubmit="return confirm('Yakin kirim/ulang pesan ke {{ $pendingOrFailed }} kontak (pending/gagal) ini?')">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send-fill"></i> Kirim / Kirim Ulang</button>
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
    <div class="card-header" style="justify-content: space-between;">
        <h3><i class="bi bi-person-lines-fill" style="color:var(--info);margin-right:8px;"></i> Status Pengiriman per Kontak</h3>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('addContactModal').style.display='flex'">
            <i class="bi bi-person-plus-fill"></i> Tambah Kontak Baru
        </button>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Nama</th><th>Nomor</th><th>Status</th><th>Pesan Error</th><th>Waktu</th><th>Aksi</th></tr>
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
                    <td>
                        @if(in_array($bc->status, ['sent', 'delivered', 'read']))
                        <form method="POST" action="{{ route('broadcasts.resetContact', $bc) }}" onsubmit="return confirm('Ubah status ke Pending agar kontak ini bisa menerima broadcast ulang?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;" title="Reset Status menjadi Pending">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kontak Baru -->
<div id="addContactModal" class="modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:100;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:700px;margin:20px;max-height:90vh;display:flex;flex-direction:column;">
        <div class="card-header" style="display:flex;justify-content:space-between;">
            <h3 style="margin:0;"><i class="bi bi-person-plus-fill" style="color:var(--accent);margin-right:8px;"></i> Tambah Kontak Tambahan</h3>
            <button type="button" onclick="document.getElementById('addContactModal').style.display='none'" style="background:none;border:none;font-size:24px;color:var(--text-muted);cursor:pointer;">&times;</button>
        </div>
        
        <form method="POST" action="{{ route('broadcasts.addContacts', $broadcast) }}" style="display:flex;flex-direction:column;flex:1;overflow:hidden;padding:24px;">
            @csrf
            
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
            
            <div id="contactList" style="flex:1;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px;min-height:300px;margin-bottom:16px;">
                @foreach($contacts as $contact)
                @php
                    $alreadyInBroadcast = $broadcast->broadcastContacts->contains('contact_id', $contact->id);
                @endphp
                @if(!$alreadyInBroadcast)
                <label class="checkbox-item contact-row" data-name="{{ strtolower($contact->name ?? '') }}" data-phone="{{ $contact->phone }}" data-category="{{ $contact->category_id ?? 'none' }}" style="margin-bottom:4px;display:flex;">
                    <input type="checkbox" name="contact_ids[]" value="{{ $contact->id }}" class="contact-checkbox">
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
                @endif
                @endforeach
                @if($contacts->isEmpty() || $broadcast->broadcastContacts->count() == $contacts->count())
                    <div style="text-align:center;padding:20px;color:var(--text-muted);">Semua kontak sudah ada di broadcast ini, atau phonebook masih kosong.</div>
                @endif
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addContactModal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambahkan Kontak ke Broadcast</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
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
        }
    });
}

function selectAllVisible() {
    document.querySelectorAll('.contact-row').forEach(row => {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.contact-checkbox');
            cb.checked = true;
            row.classList.add('checked');
        }
    });
}

function deselectAll() {
    document.querySelectorAll('.contact-checkbox').forEach(cb => {
        cb.checked = false;
        cb.closest('.checkbox-item').classList.remove('checked');
    });
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
