@extends('layouts.app')

@section('title', 'Kontak')

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <!-- Import Excel -->
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-file-earmark-excel-fill" style="color:var(--accent);margin-right:8px;"></i> Import dari Excel</h3>
        </div>
        <form method="POST" action="{{ route('contacts.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">File Excel (.xlsx, .xls, .csv)</label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                <div class="form-hint">Format kolom: <strong>phone</strong> (wajib), <strong>name</strong> (opsional). Juga mendukung: nomor, no_hp, nama, telephone, telepon</div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload"></i> Import</button>
        </form>
    </div>

    <!-- Manual Add -->
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-person-plus-fill" style="color:var(--info);margin-right:8px;"></i> Tambah Manual</h3>
        </div>
        <form method="POST" action="{{ route('contacts.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Nomor Telepon *</label>
                <input type="text" name="phone" class="form-control" placeholder="08xxxx atau 62xxxx" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama</label>
                <input type="text" name="name" class="form-control" placeholder="Nama kontak (opsional)">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah</button>
        </form>
    </div>
</div>

<!-- Contacts List -->
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-person-lines-fill" style="color:var(--accent);margin-right:8px;"></i> Daftar Kontak ({{ $contacts->total() }})</h3>
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
                    <tr><th>Nama</th><th>Nomor</th><th>Ditambahkan</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($contacts as $contact)
                    <tr>
                        <td style="font-weight:600;">{{ $contact->name ?: 'Tanpa Nama' }}</td>
                        <td><span class="phone-tag">{{ $contact->phone }}</span></td>
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
@endsection
