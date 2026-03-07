@extends('layouts.app')

@section('title', 'Buat Template')

@section('content')
<div class="card" style="max-width:800px;">
    <div class="card-header">
        <h3><i class="bi bi-plus-circle-fill" style="color:var(--accent);margin-right:8px;"></i> Buat Message Template</h3>
    </div>

    <form method="POST" action="{{ route('templates.store') }}" enctype="multipart/form-data">
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
                <label class="form-label">Kategori *</label>
                <select name="category" class="form-control" required>
                    <option value="MARKETING" {{ old('category') === 'MARKETING' ? 'selected' : '' }}>Marketing</option>
                    <option value="UTILITY" {{ old('category') === 'UTILITY' ? 'selected' : '' }}>Utility</option>
                    <option value="AUTHENTICATION" {{ old('category') === 'AUTHENTICATION' ? 'selected' : '' }}>Authentication</option>
                </select>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nama Template *</label>
                <input type="text" name="name" class="form-control" placeholder="contoh: promo_diskon_akhir_tahun" value="{{ old('name') }}" required pattern="[a-z0-9_]+">
                <div class="form-hint">Hanya huruf kecil, angka, dan underscore. Tidak boleh ada spasi.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Bahasa *</label>
                <select name="language" class="form-control" required>
                    <option value="id" {{ old('language', 'id') === 'id' ? 'selected' : '' }}>Indonesia (id)</option>
                    <option value="en_US" {{ old('language') === 'en_US' ? 'selected' : '' }}>English (en_US)</option>
                    <option value="en" {{ old('language') === 'en' ? 'selected' : '' }}>English (en)</option>
                </select>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Header Type</label>
                <select name="header_type" class="form-control" id="headerType" onchange="toggleHeader()">
                    <option value="NONE" {{ old('header_type', 'NONE') === 'NONE' ? 'selected' : '' }}>Tidak ada header</option>
                    <option value="TEXT" {{ old('header_type') === 'TEXT' ? 'selected' : '' }}>Text</option>
                    <option value="IMAGE" {{ old('header_type') === 'IMAGE' ? 'selected' : '' }}>Image</option>
                    <option value="VIDEO" {{ old('header_type') === 'VIDEO' ? 'selected' : '' }}>Video</option>
                    <option value="DOCUMENT" {{ old('header_type') === 'DOCUMENT' ? 'selected' : '' }}>Document</option>
                </select>
            </div>
            <div class="form-group" id="headerContent" style="display:none;">
                <label class="form-label">Header Text</label>
                <input type="text" name="header_content" class="form-control" placeholder="Teks header" value="{{ old('header_content') }}">
            </div>
            <div class="form-group" id="headerMedia" style="display:none;">
                <label class="form-label">Upload Media</label>
                <input type="file" name="header_media" class="form-control" id="headerMediaInput" style="padding:8px;">
                <div class="form-hint">Upload gambar (JPG/PNG, maks 5MB), video (MP4, maks 16MB), atau dokumen (PDF, maks 100MB).</div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Body Pesan *</label>
            <textarea name="body" class="form-control" rows="5" placeholder="Tuliskan isi pesan template Anda. Gunakan {{1}}, {{2}}, dll untuk variabel." required>{{ old('body') }}</textarea>
            <div class="form-hint">Gunakan &#123;&#123;1&#125;&#125;, &#123;&#123;2&#125;&#125;, dll sebagai placeholder variabel</div>
        </div>

        <div class="form-group">
            <label class="form-label">Footer</label>
            <input type="text" name="footer" class="form-control" placeholder="Teks footer (opsional, maks 60 karakter)" value="{{ old('footer') }}" maxlength="60">
        </div>

        <!-- Preview Card -->
        <div class="form-group">
            <label class="form-label">Preview</label>
            <div style="background:#0b141a;border-radius:12px;padding:16px;max-width:360px;">
                <div style="background:#005c4b;color:white;padding:10px 14px;border-radius:8px 8px 8px 2px;font-size:14px;line-height:1.5;" id="previewBody">
                    Isi pesan akan muncul di sini...
                </div>
                <div style="text-align:right;margin-top:4px;">
                    <span style="font-size:11px;color:#8696a0;">12:00 <i class="bi bi-check2-all" style="color:#53bdeb;"></i></span>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill"></i> Ajukan Template</button>
            <a href="{{ route('templates.index', ['device_id' => $deviceId]) }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
function toggleHeader() {
    const type = document.getElementById('headerType').value;
    const mediaInput = document.getElementById('headerMediaInput');
    document.getElementById('headerContent').style.display = type === 'TEXT' ? 'block' : 'none';
    document.getElementById('headerMedia').style.display = ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(type) ? 'block' : 'none';

    if (type === 'IMAGE') mediaInput.setAttribute('accept', 'image/jpeg,image/png');
    else if (type === 'VIDEO') mediaInput.setAttribute('accept', 'video/mp4,video/3gpp');
    else if (type === 'DOCUMENT') mediaInput.setAttribute('accept', '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx');
    else mediaInput.removeAttribute('accept');
}
toggleHeader();

// Live preview
const bodyInput = document.querySelector('textarea[name="body"]');
const previewBody = document.getElementById('previewBody');
bodyInput.addEventListener('input', function() {
    previewBody.textContent = this.value || 'Isi pesan akan muncul di sini...';
});
</script>
@endsection
