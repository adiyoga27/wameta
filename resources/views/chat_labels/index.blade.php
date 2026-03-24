@extends('layouts.app')

@section('title', 'Manajemen Label')

@section('actions')
<form method="GET" action="{{ route('chat-labels.index') }}" style="display:flex;gap:8px;align-items:center;">
    <select name="device_id" class="form-control" style="width:200px;padding:8px 12px;" onchange="this.form.submit()">
        @foreach($devices as $d)
            <option value="{{ $d->id }}" {{ $deviceId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
        @endforeach
    </select>
</form>
<button class="btn btn-primary" onclick="openAddModal()">
    <i class="bi bi-plus-lg"></i> Label Baru
</button>
@endsection

@section('content')
<x-tutorial title="Panduan Manajemen Label">
    <p>Di halaman ini Anda dapat mendaftarkan label-label global yang nantinya bisa Anda pasangkan pada suatu Percakapan (Nomor) atau pada masing-masing pesan balasan di menu <strong>Pesan Masuk</strong>.</p>
    <ul>
        <li>Setiap Device dapat memiliki kumpulan Label yang berbeda-beda.</li>
        <li>Gunakan warna yang beragam agar label mudah dibedakan secara visual.</li>
        <li>Satu percakapan dan pesan bisa ditandai dengan berbagai label sekaligus.</li>
    </ul>
</x-tutorial>

<div class="card">
    <div class="card-header">
        <h3>Daftar Label Tersedia</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Warna</th>
                    <th>Nama Label</th>
                    <th>Total Percakapan</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($labels as $label)
                <tr>
                    <td>
                        <div style="width: 24px; height: 24px; border-radius: 6px; background-color: {{ $label->color_hex }}; box-shadow: 0 0 10px {{ str_replace('#', 'rgba(', $label->color_hex) }}, 0.3);"></div>
                    </td>
                    <td>
                        <span style="font-weight: 600;">{{ $label->name }}</span>
                    </td>
                    <td>
                        <span class="badge badge-secondary" style="font-size:12px;">{{ \App\Models\ConversationLabel::where('chat_label_id', $label->id)->count() }}</span>
                    </td>
                    <td style="text-align: right;">
                        <button class="btn btn-secondary btn-sm" onclick="openEditModal({{ $label->id }}, '{{ addslashes($label->name) }}', '{{ $label->color_hex }}')" style="padding: 6px 10px;">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <form action="{{ route('chat-labels.destroy', $label->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus label ini? Semua percakapan yang tertaut dengan label ini akan kehilangan label ini.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 6px 10px;">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <div class="empty-state" style="padding: 40px 20px;">
                            <i class="bi bi-tags" style="font-size: 40px;"></i>
                            <h4 style="margin-top: 15px;">Belum ada Label</h4>
                            <p>Klik tombol <strong>Label Baru</strong> di kanan atas untuk membuat label pertama Anda.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add/Edit Modal --}}
<div class="modal-overlay" id="labelModal" style="display:none;">
    <div class="modal-content" style="max-width:400px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;">
        <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <h3 id="modalTitle" style="margin: 0; font-size: 18px;"><i class="bi bi-tag-fill" style="color:var(--accent); margin-right:8px;"></i> Tambah Label</h3>
            <button class="modal-close" style="background:transparent; border:none; color:var(--text-primary); font-size:24px; cursor:pointer;" onclick="closeModal()">&times;</button>
        </div>
        <form id="labelForm" method="POST" action="">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="device_id" value="{{ $deviceId }}">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Nama Label</label>
                <input type="text" name="name" id="inputName" class="form-control" placeholder="Contoh: Pelanggan Baru, Komplain, Lunas" required maxlength="50">
            </div>
            
            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label">Pilih Warna</label>
                <div style="display:flex; gap: 10px; align-items: center;">
                    <input type="color" name="color_hex" id="inputColor" value="#25D366" style="width: 50px; height: 50px; padding: 0; border: none; border-radius: 8px; cursor: pointer; background: transparent;">
                    <span style="font-family: monospace; color: var(--text-muted);" id="colorValueDisplay">#25D366</span>
                </div>
                <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                    {{-- Quick Color Presets --}}
                    @php
                        $presets = ['#25D366', '#3b82f6', '#ef4444', '#f59e0b', '#a855f7', '#ec4899', '#14b8a6', '#f97316'];
                    @endphp
                    @foreach($presets as $c)
                        <div onclick="document.getElementById('inputColor').value = '{{ $c }}'; document.getElementById('colorValueDisplay').innerText = '{{ $c }}';" 
                             style="width: 28px; height: 28px; border-radius: 50%; background-color: {{ $c }}; cursor: pointer; border: 2px solid transparent; transition: transform 0.2s;" 
                             onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'"></div>
                    @endforeach
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    <i class="bi bi-save-fill"></i> Simpan Label
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const modal = document.getElementById('labelModal');
    const form = document.getElementById('labelForm');
    const inputName = document.getElementById('inputName');
    const inputColor = document.getElementById('inputColor');
    const colorDisplay = document.getElementById('colorValueDisplay');
    const modalTitle = document.getElementById('modalTitle');
    const formMethod = document.getElementById('formMethod');

    // Update Hex text display when picker changes
    inputColor.addEventListener('input', function() {
        colorDisplay.innerText = this.value.toUpperCase();
    });

    function openAddModal() {
        modalTitle.innerHTML = '<i class="bi bi-tag-fill" style="color:var(--accent); margin-right:8px;"></i> Tambah Label';
        form.action = "{{ route('chat-labels.store') }}";
        formMethod.value = "POST";
        inputName.value = '';
        inputColor.value = '#25D366';
        colorDisplay.innerText = '#25D366';
        modal.style.display = 'flex';
        setTimeout(() => inputName.focus(), 100);
    }

    function openEditModal(id, name, color) {
        modalTitle.innerHTML = '<i class="bi bi-pencil-square" style="color:var(--accent); margin-right:8px;"></i> Edit Label';
        form.action = `/chat-labels/${id}`;
        formMethod.value = "PUT";
        inputName.value = name;
        inputColor.value = color;
        colorDisplay.innerText = color.toUpperCase();
        modal.style.display = 'flex';
        setTimeout(() => inputName.focus(), 100);
    }

    function closeModal() {
        modal.style.display = 'none';
    }
</script>
@endsection
