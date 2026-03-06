@extends('layouts.app')

@section('title', 'Pembayaran Top Up')

@section('content')
<div class="card" style="max-width:600px;margin:0 auto;text-align:center;">
    <div class="card-header" style="justify-content:center;">
        <h3><i class="bi bi-credit-card-fill" style="color:var(--accent);margin-right:8px;"></i> Pembayaran Top Up</h3>
    </div>

    <div style="margin:20px 0;">
        <div style="font-size:14px;color:var(--text-muted);margin-bottom:8px;">Device: {{ $device->name }}</div>
        <div style="font-size:36px;font-weight:800;color:var(--accent);">Rp {{ number_format($topup->amount, 0, ',', '.') }}</div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:8px;">Order ID: {{ $topup->order_id }}</div>
    </div>

    <button type="button" id="payButton" class="btn btn-primary" style="width:100%;padding:16px;font-size:16px;font-weight:700;">
        <i class="bi bi-wallet2"></i> Bayar Sekarang
    </button>

    <a href="{{ route('topups.index', ['device_id' => $device->id]) }}" class="btn btn-secondary" style="width:100%;margin-top:10px;">
        Kembali
    </a>
</div>
@endsection

@section('scripts')
@if($isProduction)
<script src="https://app.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
@else
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
@endif

<script>
document.getElementById('payButton').addEventListener('click', function() {
    window.snap.pay('{{ $snapToken }}', {
        onSuccess: function(result) {
            window.location.href = '{{ route("topups.finish") }}?order_id={{ $topup->order_id }}';
        },
        onPending: function(result) {
            window.location.href = '{{ route("topups.finish") }}?order_id={{ $topup->order_id }}';
        },
        onError: function(result) {
            alert('Pembayaran gagal!');
            window.location.href = '{{ route("topups.index", ["device_id" => $device->id]) }}';
        },
        onClose: function() {
            // User closed the popup
        }
    });
});

// Auto-open Snap on page load
setTimeout(function() {
    document.getElementById('payButton').click();
}, 500);
</script>
@endsection
