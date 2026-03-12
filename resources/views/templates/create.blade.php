@extends('layouts.app')

@section('title', 'Buat Template')

@section('content')
<x-tutorial title="Panduan Membuat Template Pesan">
    <p>Ajukan template baru ke WhatsApp Meta untuk di-review. Panduan:</p>
    <ul>
        <li><strong>Nama Template:</strong> Gunakan huruf kecil, angka, dan underscore (_) saja. Contoh: <code>promo_juni_2026</code>.</li>
        <li><strong>Header:</strong> Opsional. Anda bisa melampirkan teks tebal, gambar, video, atau dokumen sebagai pembuka. File media akan langsung di-upload ke server Meta.</li>
        <li><strong>Body:</strong> Isi pesan Anda. Anda pun bisa menggunakan variabel kustom seperti <code>{{1}}</code>, dll untuk disisipkan nama penerima nanti secara dinamis (opsional).</li>
        <li><strong>Buttons:</strong> Opsional. Tambahkan hingga 3 button (Quick Reply, URL, Telepon, Copy Code, atau Flow).</li>
        <li><strong>Format:</strong> Dilarang menggunakan kata yang terindikasi menipu, spam, atau berbau SARA. Hindari huruf kapital berlebihan.</li>
    </ul>
</x-tutorial>

<div class="card" style="max-width: 800px; margin: 0 auto;">
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

        <!-- Buttons Section -->
        <div class="form-group" id="buttonsSection">
            <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;">
                <span><i class="bi bi-hand-index-thumb" style="margin-right:6px;"></i> Buttons (Opsional, Maks 3)</span>
                <button type="button" class="btn btn-secondary btn-sm" id="addButtonBtn" onclick="addButton()" style="font-size:12px;padding:4px 12px;">
                    <i class="bi bi-plus-lg"></i> Tambah Button
                </button>
            </label>
            <div class="form-hint" style="margin-bottom:12px;">Tambahkan tombol aksi di bawah pesan template. Maks 3 button per template sesuai ketentuan Meta.</div>

            <div id="buttonsContainer"></div>
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
                <!-- Preview Buttons -->
                <div id="previewButtons" style="margin-top:8px;display:flex;flex-direction:column;gap:6px;"></div>
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
<style>
    .button-row {
        background: var(--bg-secondary, #1a1f2e);
        border: 1px solid var(--border, #2d3348);
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
        position: relative;
        transition: all 0.2s ease;
    }
    .button-row:hover {
        border-color: var(--accent, #00d47e);
    }
    .button-row .btn-remove {
        position: absolute;
        top: 8px;
        right: 8px;
        background: var(--danger, #ff4757);
        color: white;
        border: none;
        border-radius: 6px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    .button-row .btn-remove:hover {
        background: #e8384f;
        transform: scale(1.1);
    }
    .button-row .button-fields {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .button-row .button-fields .full-width {
        grid-column: 1 / -1;
    }
    .button-row .button-number {
        font-size: 11px;
        font-weight: 700;
        color: var(--accent, #00d47e);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .preview-btn {
        background: rgba(0, 92, 75, 0.6);
        border: 1px solid rgba(255,255,255,0.1);
        color: #53bdeb;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
</style>

<script>
let buttonCount = 0;
const MAX_BUTTONS = 3;

function addButton() {
    if (buttonCount >= MAX_BUTTONS) return;
    buttonCount++;

    const container = document.getElementById('buttonsContainer');
    const row = document.createElement('div');
    row.className = 'button-row';
    row.dataset.index = buttonCount;
    row.id = 'buttonRow' + buttonCount;

    const idx = buttonCount - 1;

    row.innerHTML = `
        <div class="button-number">Button ${buttonCount}</div>
        <button type="button" class="btn-remove" onclick="removeButton(this)" title="Hapus button">&times;</button>
        <div class="button-fields">
            <div>
                <label class="form-label" style="font-size:12px;">Tipe *</label>
                <select name="buttons[${idx}][type]" class="form-control" onchange="toggleButtonFields(this)" required style="font-size:13px;">
                    <option value="QUICK_REPLY">Quick Reply</option>
                    <option value="URL">URL</option>
                    <option value="PHONE_NUMBER">Telepon</option>
                    <option value="COPY_CODE">Copy Code</option>
                    <option value="FLOW">Flow</option>
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:12px;">Label Button *</label>
                <input type="text" name="buttons[${idx}][text]" class="form-control" placeholder="Teks tombol" required style="font-size:13px;" oninput="updatePreviewButtons()">
            </div>
            <div class="full-width btn-field-url" style="display:none;">
                <label class="form-label" style="font-size:12px;">URL *</label>
                <input type="url" name="buttons[${idx}][url]" class="form-control" placeholder="https://contoh.com/halaman" style="font-size:13px;">
                <div class="form-hint">Gunakan {{1}} di akhir URL untuk variabel dinamis. Contoh: https://toko.com/order/{{1}}</div>
            </div>
            <div class="full-width btn-field-phone" style="display:none;">
                <label class="form-label" style="font-size:12px;">Nomor Telepon *</label>
                <input type="tel" name="buttons[${idx}][phone_number]" class="form-control" placeholder="+6281234567890" style="font-size:13px;">
            </div>
            <div class="full-width btn-field-code" style="display:none;">
                <label class="form-label" style="font-size:12px;">Contoh Kode *</label>
                <input type="text" name="buttons[${idx}][copy_code]" class="form-control" placeholder="contoh: DISCOUNT20" style="font-size:13px;">
                <div class="form-hint">Kode contoh yang akan dicopy oleh penerima</div>
            </div>
            <div class="btn-field-flow" style="display:none;">
                <label class="form-label" style="font-size:12px;">Flow ID *</label>
                <input type="text" name="buttons[${idx}][flow_id]" class="form-control" placeholder="Flow ID dari Meta" style="font-size:13px;">
            </div>
            <div class="btn-field-flow-action" style="display:none;">
                <label class="form-label" style="font-size:12px;">Flow Action</label>
                <select name="buttons[${idx}][flow_action]" class="form-control" style="font-size:13px;">
                    <option value="navigate">Navigate</option>
                    <option value="data_exchange">Data Exchange</option>
                </select>
            </div>
        </div>
    `;

    container.appendChild(row);
    updateAddButton();
    updatePreviewButtons();
}

function removeButton(btn) {
    const row = btn.closest('.button-row');
    row.remove();
    buttonCount--;

    // Re-index remaining buttons
    const rows = document.querySelectorAll('#buttonsContainer .button-row');
    rows.forEach((r, i) => {
        r.querySelector('.button-number').textContent = 'Button ' + (i + 1);
        r.id = 'buttonRow' + (i + 1);
        // Update name attributes
        r.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/buttons\[\d+\]/, `buttons[${i}]`);
        });
    });

    updateAddButton();
    updatePreviewButtons();
}

function toggleButtonFields(select) {
    const row = select.closest('.button-row');
    const type = select.value;

    row.querySelector('.btn-field-url').style.display = type === 'URL' ? 'block' : 'none';
    row.querySelector('.btn-field-phone').style.display = type === 'PHONE_NUMBER' ? 'block' : 'none';
    row.querySelector('.btn-field-code').style.display = type === 'COPY_CODE' ? 'block' : 'none';

    const flowFields = row.querySelectorAll('.btn-field-flow, .btn-field-flow-action');
    flowFields.forEach(f => f.style.display = type === 'FLOW' ? 'block' : 'none');

    // Toggle required
    const urlInput = row.querySelector('[name$="[url]"]');
    const phoneInput = row.querySelector('[name$="[phone_number]"]');
    const codeInput = row.querySelector('[name$="[copy_code]"]');
    const flowIdInput = row.querySelector('[name$="[flow_id]"]');

    if (urlInput) urlInput.required = (type === 'URL');
    if (phoneInput) phoneInput.required = (type === 'PHONE_NUMBER');
    if (codeInput) codeInput.required = (type === 'COPY_CODE');
    if (flowIdInput) flowIdInput.required = (type === 'FLOW');

    updatePreviewButtons();
}

function updateAddButton() {
    const btn = document.getElementById('addButtonBtn');
    if (buttonCount >= MAX_BUTTONS) {
        btn.style.display = 'none';
    } else {
        btn.style.display = 'inline-flex';
    }
}

function updatePreviewButtons() {
    const container = document.getElementById('previewButtons');
    container.innerHTML = '';

    const rows = document.querySelectorAll('#buttonsContainer .button-row');
    rows.forEach(row => {
        const type = row.querySelector('select[name$="[type]"]').value;
        const text = row.querySelector('input[name$="[text]"]').value || 'Button';

        const previewBtn = document.createElement('div');
        previewBtn.className = 'preview-btn';

        let icon = '';
        if (type === 'URL') icon = '<i class="bi bi-box-arrow-up-right"></i>';
        else if (type === 'PHONE_NUMBER') icon = '<i class="bi bi-telephone-fill"></i>';
        else if (type === 'QUICK_REPLY') icon = '<i class="bi bi-reply-fill"></i>';
        else if (type === 'COPY_CODE') icon = '<i class="bi bi-clipboard"></i>';
        else if (type === 'FLOW') icon = '<i class="bi bi-diagram-3"></i>';

        previewBtn.innerHTML = icon + ' ' + text;
        container.appendChild(previewBtn);
    });
}

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
