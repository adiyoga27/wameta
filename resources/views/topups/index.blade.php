@extends('layouts.app')

@section('title', 'Top Up Saldo')

@section('actions')
<form method="GET" action="{{ route('topups.index') }}" style="display:flex;gap:8px;align-items:center;">
    <select name="device_id" class="form-control" style="width:200px;padding:8px 12px;" onchange="this.form.submit()">
        @foreach($devices as $d)
            <option value="{{ $d->id }}" {{ $deviceId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
        @endforeach
    </select>
</form>
@endsection

@section('content')

<!-- Balance Card -->
<div class="card" style="margin-bottom:20px;background:linear-gradient(135deg, rgba(37,211,102,0.12) 0%, rgba(0,136,204,0.08) 100%);">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <div style="font-size:13px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Saldo Device</div>
            <div style="font-size:36px;font-weight:800;color:var(--accent);line-height:1.1;">
                Rp {{ number_format($device->balance ?? 0, 0, ',', '.') }}
            </div>
            <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">{{ $device->name }}</div>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('topupModal').classList.add('show')">
                <i class="bi bi-plus-circle-fill"></i> Top Up Saldo
            </button>
        </div>
    </div>
</div>

<!-- Quick Amount Topup Form (Modal) -->
<div class="modal-overlay" id="topupModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="bi bi-wallet2" style="color:var(--accent);margin-right:8px;"></i> Top Up Saldo</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('topupModal').classList.remove('show')">&times;</button>
        </div>
        <form method="POST" action="{{ route('topups.store') }}">
            @csrf
            <input type="hidden" name="device_id" value="{{ $deviceId }}">

            <div class="form-group">
                <label class="form-label">Device</label>
                <div style="font-weight:600;padding:10px 14px;background:var(--bg-primary);border-radius:8px;border:1px solid var(--border);">{{ $device->name }}</div>
            </div>

            <div class="form-group">
                <label class="form-label">Pilih Nominal</label>
                <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:8px;">
                    @foreach([50000, 100000, 200000, 500000, 1000000, 5000000] as $nom)
                    <button type="button" class="btn btn-secondary amount-btn" onclick="selectAmount({{ $nom }})" style="padding:12px;text-align:center;">
                        <div style="font-weight:700;font-size:14px;">Rp {{ number_format($nom, 0, ',', '.') }}</div>
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Atau Masukkan Jumlah (min Rp 10.000)</label>
                <input type="number" name="amount" id="amountInput" class="form-control" placeholder="Contoh: 100000" min="10000" max="100000000" required>
                <div class="form-hint">Format: angka tanpa titik, contoh 100000</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px;">
                <i class="bi bi-credit-card-fill"></i> Bayar Sekarang
            </button>
        </form>
    </div>
</div>

<!-- History -->
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-clock-history" style="color:var(--info);margin-right:8px;"></i> Riwayat Top Up</h3>
    </div>
    @if($topups->isEmpty())
        <div class="empty-state">
            <i class="bi bi-wallet2"></i>
            <h4>Belum ada riwayat top up</h4>
            <p>Klik tombol "Top Up Saldo" untuk memulai</p>
        </div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Order ID</th><th>Oleh</th><th>Jumlah</th><th>Metode</th><th>Status</th><th>Waktu</th></tr>
                </thead>
                <tbody>
                    @foreach($topups as $t)
                    <tr>
                        <td style="font-size:12px;font-family:monospace;">{{ $t->order_id }}</td>
                        <td>{{ $t->user->name ?? '-' }}</td>
                        <td style="font-weight:700;color:var(--accent);">Rp {{ number_format($t->amount, 0, ',', '.') }}</td>
                        <td>
                            @if($t->payment_type)
                                <span class="badge badge-info">{{ $t->payment_type }}</span>
                            @else
                                <span class="badge badge-secondary">-</span>
                            @endif
                        </td>
                        <td>
                            @switch($t->status)
                                @case('settlement')
                                @case('capture')
                                    <span class="badge badge-success"><i class="bi bi-check-circle"></i> Berhasil</span> @break
                                @case('pending')
                                    <span class="badge badge-warning"><i class="bi bi-clock"></i> Pending</span>
                                    @if($t->snap_token)
                                        <a href="javascript:void(0)" onclick="payExisting('{{ $t->snap_token }}')" class="btn btn-primary btn-sm" style="margin-left:4px;padding:2px 8px;font-size:11px;">Bayar</a>
                                    @endif
                                    @break
                                @case('expire')
                                    <span class="badge badge-secondary"><i class="bi bi-x-circle"></i> Expired</span> @break
                                @case('cancel')
                                    <span class="badge badge-secondary"><i class="bi bi-x-circle"></i> Dibatalkan</span> @break
                                @case('deny')
                                @case('failure')
                                    <span class="badge badge-danger"><i class="bi bi-x-circle"></i> Gagal</span> @break
                                @default
                                    <span class="badge badge-secondary">{{ $t->status }}</span>
                            @endswitch
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                            {{ $t->paid_at ? $t->paid_at->format('d M Y H:i') : $t->created_at->format('d M Y H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">
            {{ $topups->appends(['device_id' => $deviceId])->links('pagination::simple-bootstrap-5') }}
        </div>
    @endif
</div>

<style>
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:1000; align-items:center; justify-content:center; }
.modal-overlay.show { display:flex; }
.modal-content { background:var(--bg-secondary); border:1px solid var(--border); border-radius:16px; padding:28px; width:100%; max-width:500px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.4); }
.modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
.modal-header h3 { margin:0; font-size:18px; }
.amount-btn { transition: all 0.2s; }
.amount-btn:hover, .amount-btn.selected { background:rgba(37,211,102,0.15) !important; border-color:var(--accent) !important; color:var(--accent) !important; }
</style>
@endsection

@section('scripts')
<!-- Midtrans Snap JS -->
<script src="{{ $device->id ? 'https://app.sandbox.midtrans.com/snap/snap.js' : 'https://app.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
<script>
function selectAmount(amount) {
    document.getElementById('amountInput').value = amount;
    document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('selected'));
    event.target.closest('.amount-btn').classList.add('selected');
}

function payExisting(token) {
    window.snap.pay(token, {
        onSuccess: function(r) { window.location.href = '{{ route("topups.index", ["device_id" => $deviceId]) }}'; },
        onPending: function(r) { window.location.href = '{{ route("topups.index", ["device_id" => $deviceId]) }}'; },
        onError: function(r) { alert('Pembayaran gagal!'); },
        onClose: function() {}
    });
}
</script>
@endsection
