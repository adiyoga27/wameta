@extends('layouts.app')

@section('title', 'Edit Template: ' . $template->name)

@section('content')
<x-tutorial title="Panduan Edit Template Pesan (Meta)">
    <p>Karena aturan Meta API, beberapa field tidak bisa diubah setelah template dibuat (seperti Nama dan Bahasa).</p>
    <ul>
        <li><strong>Status:</strong> Mengedit template akan mengubah statusnya kembali menjadi PENDING untuk direview ulang oleh Meta.</li>
        <li><strong>Kategori:</strong> Pastikan kategori sesuai dengan tujuan template (Marketing, Utility, dll).</li>
        <li><strong>Header:</strong> Jika menggunakan media (gambar/video/dokumen), upload ulang file jika Anda ingin menggantinya. Kosongkan jika tidak ingin upload ulang.</li>
    </ul>
</x-tutorial>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3><i class="bi bi-pencil-square" style="color:var(--accent);margin-right:8px;"></i> Edit Template: {{ $template->name }}</h3>
    </div>

    <form method="POST" action="{{ route('templates.update', $template->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <input type="hidden" name="device_id" value="{{ $template->device_id }}">
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nama Template</label>
                <input type="text" name="name" class="form-control" value="{{ $template->name }}" readonly style="background-color: var(--bg-hover); cursor: not-allowed; color: var(--text-muted);">
                <div class="form-hint">Nama template tidak dapat diubah dari Meta.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Bahasa</label>
                <input type="text" name="language" class="form-control" value="{{ $template->language }}" readonly style="background-color: var(--bg-hover); cursor: not-allowed; color: var(--text-muted);">
                <div class="form-hint">Bahasa template tidak dapat diubah dari Meta.</div>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Kategori *</label>
                <select name="category" class="form-control" required>
                    <option value="MARKETING" {{ old('category', $template->category) === 'MARKETING' ? 'selected' : '' }}>Marketing</option>
                    <option value="UTILITY" {{ old('category', $template->category) === 'UTILITY' ? 'selected' : '' }}>Utility</option>
                    <option value="AUTHENTICATION" {{ old('category', $template->category) === 'AUTHENTICATION' ? 'selected' : '' }}>Authentication</option>
                </select>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Header Type</label>
                <select name="header_type" class="form-control" id="headerType" onchange="toggleHeader()">
                    <option value="NONE" {{ old('header_type', $template->header_type) === 'NONE' ? 'selected' : '' }}>Tidak ada header</option>
                    <option value="TEXT" {{ old('header_type', $template->header_type) === 'TEXT' ? 'selected' : '' }}>Text</option>
                    <option value="IMAGE" {{ old('header_type', $template->header_type) === 'IMAGE' ? 'selected' : '' }}>Image</option>
                    <option value="VIDEO" {{ old('header_type', $template->header_type) === 'VIDEO' ? 'selected' : '' }}>Video</option>
                    <option value="DOCUMENT" {{ old('header_type', $template->header_type) === 'DOCUMENT' ? 'selected' : '' }}>Document</option>
                </select>
            </div>
            <div class="form-group" id="headerContent" style="display:none;">
                <label class="form-label">Header Text</label>
                <input type="text" name="header_content" class="form-control" placeholder="Teks header" value="{{ old('header_content', $template->header_content) }}">
            </div>
            <div class="form-group" id="headerMedia" style="display:none;">
                <label class="form-label">Upload Media Ulang (Opsional)</label>
                <input type="file" name="header_media" class="form-control" id="headerMediaInput" style="padding:8px;">
                <div class="form-hint">Hanya upload jika Anda ingin mengganti media header. Kosongkan jika ingin menggunakan media yang sama.</div>
                @if($template->header_media_path)
                    <div style="margin-top:6px;font-size:12px;color:rgba(255,255,255,0.7);background:var(--bg-hover);padding:6px 10px;border-radius:6px;border:1px solid var(--border);display:inline-block;"><i class="bi bi-check2-circle" style="color:var(--accent);"></i> Media saat ini masih tersimpan</div>
                @endif
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Body Pesan *</label>
            <textarea name="body" class="form-control" rows="5" required>{{ old('body', $template->body) }}</textarea>
            <div class="form-hint">Gunakan &#123;&#123;1&#125;&#125;, &#123;&#123;2&#125;&#125;, dll sebagai placeholder variabel</div>
        </div>

        <div class="form-group">
            <label class="form-label">Footer</label>
            <input type="text" name="footer" class="form-control" value="{{ old('footer', $template->footer) }}" maxlength="60">
        </div>

        <!-- Buttons Section -->
        <div class="form-group" id="buttonsSection">
            <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;">
                <span><i class="bi bi-hand-index-thumb" style="margin-right:6px;"></i> Buttons (Opsional, Maks 3)</span>
                <button type="button" class="btn btn-secondary btn-sm" id="addButtonBtn" onclick="addButton()" style="font-size:12px;padding:4px 12px;">
                    <i class="bi bi-plus-lg"></i> Tambah Button
                </button>
            </label>
            
            <div id="buttonsContainer"></div>
        </div>

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Template Meta</button>
            <a href="{{ route('templates.index', ['device_id' => $template->device_id]) }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<style>
    .button-row { background: var(--bg-secondary, #1a1f2e); border: 1px solid var(--border, #2d3348); border-radius: 10px; padding: 14px; margin-bottom: 10px; position: relative; transition: all 0.2s ease; }
    .button-row:hover { border-color: var(--accent, #00d47e); }
    .button-row .btn-remove { position: absolute; top: 8px; right: 8px; background: var(--danger, #ff4757); color: white; border: none; border-radius: 6px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; transition: all 0.2s ease; }
    .button-row .btn-remove:hover { background: #e8384f; transform: scale(1.1); }
    .button-row .button-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .button-row .button-fields .full-width { grid-column: 1 / -1; }
    .button-row .button-number { font-size: 11px; font-weight: 700; color: var(--accent, #00d47e); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
</style>

<script>
let buttonCount = 0;
const MAX_BUTTONS = 3;

// Initialize existings buttons from original template
const existingButtons = @json(old('buttons', $template->buttons ?: []));
if(existingButtons && existingButtons.length > 0) {
    existingButtons.forEach(btn => {
        addButton(btn);
    });
}

function addButton(initialData = null) {
    if (buttonCount >= MAX_BUTTONS) return;
    buttonCount++;

    const container = document.getElementById('buttonsContainer');
    const row = document.createElement('div');
    row.className = 'button-row';
    row.dataset.index = buttonCount;
    row.id = 'buttonRow' + buttonCount;

    const idx = buttonCount - 1;
    
    // Fallback data
    const dType = initialData?.type || 'QUICK_REPLY';
    const dText = initialData?.text || '';
    const dUrl = initialData?.url || '';
    const dPhone = initialData?.phone_number || '';
    const dCode = initialData?.copy_code || '';
    const dFlowId = initialData?.flow_id || '';
    const dFlowAction = initialData?.flow_action || 'navigate';

    row.innerHTML = `
        <div class="button-number">Button ${buttonCount}</div>
        <button type="button" class="btn-remove" onclick="removeButton(this)" title="Hapus button">&times;</button>
        <div class="button-fields">
            <div>
                <label class="form-label" style="font-size:12px;">Tipe *</label>
                <select name="buttons[${idx}][type]" class="form-control" onchange="toggleButtonFields(this)" required style="font-size:13px;">
                    <option value="QUICK_REPLY" ${dType === 'QUICK_REPLY' ? 'selected' : ''}>Quick Reply</option>
                    <option value="URL" ${dType === 'URL' ? 'selected' : ''}>URL</option>
                    <option value="PHONE_NUMBER" ${dType === 'PHONE_NUMBER' ? 'selected' : ''}>Telepon</option>
                    <option value="COPY_CODE" ${dType === 'COPY_CODE' ? 'selected' : ''}>Copy Code</option>
                    <option value="FLOW" ${dType === 'FLOW' ? 'selected' : ''}>Flow</option>
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:12px;">Label Button *</label>
                <input type="text" name="buttons[${idx}][text]" class="form-control" value="${dText}" required style="font-size:13px;">
            </div>
            <div class="full-width btn-field-url" style="display:${dType === 'URL' ? 'block' : 'none'};">
                <label class="form-label" style="font-size:12px;">URL *</label>
                <input type="url" name="buttons[${idx}][url]" class="form-control" value="${dUrl}" style="font-size:13px;" ${dType === 'URL' ? 'required' : ''}>
            </div>
            <div class="full-width btn-field-phone" style="display:${dType === 'PHONE_NUMBER' ? 'block' : 'none'};">
                <label class="form-label" style="font-size:12px;">Nomor Telepon *</label>
                <input type="tel" name="buttons[${idx}][phone_number]" class="form-control" value="${dPhone}" style="font-size:13px;" ${dType === 'PHONE_NUMBER' ? 'required' : ''}>
            </div>
            <div class="full-width btn-field-code" style="display:${dType === 'COPY_CODE' ? 'block' : 'none'};">
                <label class="form-label" style="font-size:12px;">Contoh Kode *</label>
                <input type="text" name="buttons[${idx}][copy_code]" class="form-control" value="${dCode}" style="font-size:13px;" ${dType === 'COPY_CODE' ? 'required' : ''}>
            </div>
            <div class="btn-field-flow" style="display:${dType === 'FLOW' ? 'block' : 'none'};">
                <label class="form-label" style="font-size:12px;">Flow ID *</label>
                <input type="text" name="buttons[${idx}][flow_id]" class="form-control" value="${dFlowId}" style="font-size:13px;" ${dType === 'FLOW' ? 'required' : ''}>
            </div>
            <div class="btn-field-flow-action" style="display:${dType === 'FLOW' ? 'block' : 'none'};">
                <label class="form-label" style="font-size:12px;">Flow Action</label>
                <select name="buttons[${idx}][flow_action]" class="form-control" style="font-size:13px;">
                    <option value="navigate" ${dFlowAction === 'navigate' ? 'selected' : ''}>Navigate</option>
                    <option value="data_exchange" ${dFlowAction === 'data_exchange' ? 'selected' : ''}>Data Exchange</option>
                </select>
            </div>
        </div>
    `;

    container.appendChild(row);
    updateAddButton();
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
        r.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/buttons\[\d+\]/, `buttons[${i}]`);
        });
    });

    updateAddButton();
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
}

function updateAddButton() {
    const btn = document.getElementById('addButtonBtn');
    if (buttonCount >= MAX_BUTTONS) {
        btn.style.display = 'none';
    } else {
        btn.style.display = 'inline-flex';
    }
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
</script>
@endsection
