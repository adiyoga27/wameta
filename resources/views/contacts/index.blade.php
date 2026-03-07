@extends('layouts.app')

@section('title', 'Kontak')

@section('content')
<x-tutorial title="Panduan Manajemen Kontak & Kategori">
    <p>Gunakan halaman ini sebagai buku telepon (phonebook) digital yang terorganisir, khusus untuk nomor-nomor yang bersedia dikirim pesan WhatsApp oleh Anda (Opt-in).</p>
    <ul>
        <li><strong>Membuat Kategori:</strong> Buat kategori (misal: "VIP Customer", "Tamu Undangan") lewat tombol sebelah kiri atau tombol <i class="bi bi-folder-plus"></i> Kategori Baru. Kategori sangat berguna saat Anda membuat Broadcast nantinya.</li>
        <li><strong>Import Excel:</strong> Klik <strong>Import Excel</strong> untuk memasukkan ribuan nomor sekaligus dari file Excel (.xlsx). Anda bisa menetapkan kategori tertentu untuk nomor-nomor yang diimport tersebut.</li>
        <li>Nomor wajib menggunakan kode negara (contoh `6281...`, bukan `081...` atau `+62...`).</li>
    </ul>
</x-tutorial>

<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;">

    {{-- LEFT: Categories Sidebar --}}
    <div>
        {{-- Category List --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header" style="padding:12px 16px;">
                <h3 style="font-size:15px;margin:0;"><i class="bi bi-bookmark-fill" style="color:var(--accent);margin-right:6px;"></i> Kategori Phonebook</h3>
            </div>
            <div style="padding:8px;">
                {{-- All contacts --}}
                <a href="{{ route('contacts.index') }}"
                   style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:8px;text-decoration:none;color:var(--text-primary);font-size:13px;font-weight:{{ !$categoryId ? '600' : '400' }};background:{{ !$categoryId ? 'var(--bg-hover)' : 'transparent' }};margin-bottom:2px;transition:background 0.2s;"
                   onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='{{ !$categoryId ? 'var(--bg-hover)' : 'transparent' }}'">
                    <span><i class="bi bi-people-fill" style="margin-right:8px;color:var(--accent);"></i> Semua Kontak</span>
                    <span class="badge badge-secondary" style="font-size:10px;">{{ $contacts->total() }}</span>
                </a>
                {{-- Uncategorized --}}
                <a href="{{ route('contacts.index', ['category_id' => 'uncategorized']) }}"
                   style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:8px;text-decoration:none;color:var(--text-primary);font-size:13px;font-weight:{{ $categoryId === 'uncategorized' ? '600' : '400' }};background:{{ $categoryId === 'uncategorized' ? 'var(--bg-hover)' : 'transparent' }};margin-bottom:2px;transition:background 0.2s;"
                   onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='{{ $categoryId === 'uncategorized' ? 'var(--bg-hover)' : 'transparent' }}'">
                    <span><i class="bi bi-tag" style="margin-right:8px;color:var(--text-muted);"></i> Tanpa Kategori</span>
                </a>

                @foreach($categories as $cat)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 12px;border-radius:8px;background:{{ $categoryId == $cat->id ? 'var(--bg-hover)' : 'transparent' }};margin-bottom:2px;transition:background 0.2s;"
                     onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='{{ $categoryId == $cat->id ? 'var(--bg-hover)' : 'transparent' }}'">
                    <a href="{{ route('contacts.index', ['category_id' => $cat->id]) }}"
                       style="display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text-primary);font-size:13px;font-weight:{{ $categoryId == $cat->id ? '600' : '400' }};flex:1;padding:4px 0;">
                        <span style="width:10px;height:10px;border-radius:50%;background:{{ $cat->color }};flex-shrink:0;"></span>
                        <span>{{ $cat->name }}</span>
                        <span class="badge badge-secondary" style="font-size:10px;">{{ $cat->contacts_count }}</span>
                    </a>
                    <div style="display:flex;gap:2px;">
                        <button onclick="editCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->color }}', '{{ addslashes($cat->description ?? '') }}')" class="btn btn-secondary btn-sm" style="padding:2px 6px;font-size:10px;" title="Edit"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('contact-categories.destroy', $cat) }}" onsubmit="return confirm('Hapus kategori {{ $cat->name }}? Kontak tetap ada.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" style="padding:2px 6px;font-size:10px;" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Add Category --}}
        <div class="card">
            <div class="card-header" style="padding:12px 16px;">
                <h3 style="font-size:14px;margin:0;"><i class="bi bi-plus-circle-fill" style="color:var(--info);margin-right:6px;"></i> Tambah Kategori</h3>
            </div>
            <form method="POST" action="{{ route('contact-categories.store') }}" style="padding:12px 16px;">
                @csrf
                <div class="form-group" style="margin-bottom:8px;">
                    <input type="text" name="name" class="form-control" placeholder="Nama kategori" required style="padding:8px 12px;font-size:13px;">
                </div>
                <div style="display:flex;gap:8px;margin-bottom:8px;">
                    <div style="flex:1;">
                        <input type="color" name="color" class="form-control" value="#25D366" style="padding:4px;height:36px;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <input type="text" name="description" class="form-control" placeholder="Deskripsi (opsional)" style="padding:8px 12px;font-size:13px;">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="width:100%;font-size:12px;"><i class="bi bi-plus-lg"></i> Tambah</button>
            </form>
        </div>
    </div>

    {{-- RIGHT: Main Content --}}
    <div>
        {{-- Top: Import + Manual Add --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            {{-- Import Excel --}}
            <div class="card">
                <div class="card-header" style="padding:12px 16px;">
                    <h3 style="font-size:14px;margin:0;"><i class="bi bi-file-earmark-excel-fill" style="color:var(--accent);margin-right:6px;"></i> Import dari Excel</h3>
                </div>
                <form method="POST" action="{{ route('contacts.import') }}" enctype="multipart/form-data" style="padding:12px 16px;">
                    @csrf
                    <div class="form-group" style="margin-bottom:8px;">
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required style="font-size:12px;">
                        <div class="form-hint" style="font-size:10px;">Kolom: <strong>phone</strong> (wajib), <strong>name</strong> (opsional)</div>
                    </div>
                    <div class="form-group" style="margin-bottom:10px;">
                        <select name="category_id" class="form-control" style="padding:8px 12px;font-size:12px;">
                            <option value="">-- Tanpa Kategori --</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="font-size:12px;"><i class="bi bi-upload"></i> Import</button>
                </form>
            </div>

            {{-- Manual Add --}}
            <div class="card">
                <div class="card-header" style="padding:12px 16px;">
                    <h3 style="font-size:14px;margin:0;"><i class="bi bi-person-plus-fill" style="color:var(--info);margin-right:6px;"></i> Tambah Manual</h3>
                </div>
                <form method="POST" action="{{ route('contacts.store') }}" style="padding:12px 16px;">
                    @csrf
                    <div class="form-group" style="margin-bottom:8px;">
                        <input type="text" name="phone" class="form-control" placeholder="08xxxx atau 62xxxx" required style="padding:8px 12px;font-size:13px;">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
                        <input type="text" name="name" class="form-control" placeholder="Nama (opsional)" style="padding:8px 12px;font-size:13px;">
                        <select name="category_id" class="form-control" style="padding:8px 12px;font-size:12px;">
                            <option value="">-- Kategori --</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="font-size:12px;"><i class="bi bi-plus-lg"></i> Tambah</button>
                </form>
            </div>
        </div>

        {{-- Search Bar --}}
        <div style="margin-bottom:16px;">
            <form method="GET" action="{{ route('contacts.index') }}" style="display:flex;gap:8px;">
                @if($categoryId)
                <input type="hidden" name="category_id" value="{{ $categoryId }}">
                @endif
                <input type="text" name="search" class="form-control" placeholder="Cari nama atau nomor..." value="{{ $search ?? '' }}" style="flex:1;padding:10px 14px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                @if($search)
                <a href="{{ route('contacts.index', $categoryId ? ['category_id' => $categoryId] : []) }}" class="btn btn-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
        </div>

        {{-- Contact List --}}
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="bi bi-person-lines-fill" style="color:var(--accent);margin-right:8px;"></i>
                    @if($categoryId && $categoryId !== 'uncategorized')
                        @php $currentCat = $categories->firstWhere('id', $categoryId); @endphp
                        @if($currentCat)
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:{{ $currentCat->color }};margin-right:6px;vertical-align:middle;"></span>
                            {{ $currentCat->name }}
                        @endif
                    @elseif($categoryId === 'uncategorized')
                        Tanpa Kategori
                    @else
                        Daftar Kontak
                    @endif
                    <span style="font-size:14px;font-weight:400;color:var(--text-muted);margin-left:8px;">({{ $contacts->total() }})</span>
                </h3>
            </div>
            @if($contacts->isEmpty())
                <div class="empty-state">
                    <i class="bi bi-person-lines-fill"></i>
                    <h4>Belum ada kontak</h4>
                    <p>Import dari Excel atau tambah manual</p>
                </div>
            @else
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Nomor</th>
                                <th>Kategori</th>
                                <th>Ditambahkan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contacts as $contact)
                            <tr>
                                <td style="font-weight:600;">{{ $contact->name ?: 'Tanpa Nama' }}</td>
                                <td><span class="phone-tag">{{ $contact->phone }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('contacts.updateCategory', $contact) }}" style="display:inline;">
                                        @csrf @method('PATCH')
                                        <select name="category_id" class="form-control" style="padding:4px 8px;font-size:11px;min-width:120px;border-radius:6px;" onchange="this.form.submit()">
                                            <option value="">-- Tanpa --</option>
                                            @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ $contact->category_id == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td style="font-size:12px;color:var(--text-muted);">{{ $contact->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('Hapus kontak ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    {{ $contacts->links('pagination::simple-bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Edit Category Modal --}}
<div id="editCategoryModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:var(--bg-secondary);border-radius:16px;padding:24px;width:380px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,0.4);">
        <h3 style="margin-bottom:16px;"><i class="bi bi-pencil-square" style="color:var(--accent);margin-right:8px;"></i> Edit Kategori</h3>
        <form id="editCategoryForm" method="POST">
            @csrf @method('PUT')
            <div class="form-group" style="margin-bottom:10px;">
                <label class="form-label">Nama Kategori</label>
                <input type="text" name="name" id="editCatName" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom:10px;">
                <label class="form-label">Warna</label>
                <input type="color" name="color" id="editCatColor" class="form-control" style="height:40px;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" id="editCatDesc" class="form-control">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name, color, description) {
    document.getElementById('editCategoryForm').action = '/contact-categories/' + id;
    document.getElementById('editCatName').value = name;
    document.getElementById('editCatColor').value = color;
    document.getElementById('editCatDesc').value = description;
    document.getElementById('editCategoryModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
}
document.getElementById('editCategoryModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
@endsection
