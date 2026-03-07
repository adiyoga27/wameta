@props(['title' => 'Panduan Penggunaan'])

@push('topbar_actions')
    <button type="button" class="btn btn-secondary btn-icon" onclick="document.getElementById('tutorialModal').classList.add('active')" title="{{ $title }}">
        <i class="bi bi-question-lg" style="font-size: 16px;"></i>
    </button>
@endpush

<div class="modal-overlay" id="tutorialModal" onclick="if(event.target === this) this.classList.remove('active')">
    <div class="modal" style="max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px; color: var(--accent);">
                <i class="bi bi-info-circle-fill"></i>
                {{ $title }}
            </h3>
            <button type="button" class="btn btn-icon" style="background: transparent; color: var(--text-muted); border: none;" onclick="document.getElementById('tutorialModal').classList.remove('active')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="tutorial-content" style="font-size: 14px; line-height: 1.6; color: var(--text-secondary);">
            {{ $slot }}
        </div>
        
        <div style="margin-top: 25px; text-align: right; border-top: 1px solid var(--border); padding-top: 15px;">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('tutorialModal').classList.remove('active')">Tutup Panduan</button>
        </div>
    </div>
</div>

<style>
.tutorial-content ul, .tutorial-content ol {
    margin-top: 12px;
    margin-bottom: 16px;
    padding-left: 24px;
}
.tutorial-content li {
    margin-bottom: 8px;
}
.tutorial-content code {
    background: var(--bg-primary);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 13px;
    color: var(--accent);
    border: 1px solid var(--border);
}
.tutorial-content p {
    margin-bottom: 12px;
}
.tutorial-content strong {
    color: var(--text-primary);
}
</style>
