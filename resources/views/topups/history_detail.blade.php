@extends('layouts.app')

@section('title', 'Detail Riwayat ' . \Carbon\Carbon::parse($date)->translatedFormat('d F Y'))

@section('actions')
<a href="{{ route('topups.index', ['device_id' => $deviceId]) }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Saldo</a>
@endsection

@section('content')

@if($deviceId !== 'all' && $device)
<div class="card" style="margin-bottom:20px;background:linear-gradient(135deg, rgba(37,211,102,0.12) 0%, rgba(0,136,204,0.08) 100%);">
    <div style="display:flex;align-items:center;">
        <div>
            <div style="font-size:13px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Device</div>
            <div style="font-size:24px;font-weight:800;color:var(--accent);line-height:1.1;">
                {{ $device->name }}
            </div>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-list-check" style="color:var(--info);margin-right:8px;"></i> Rincian Pesan Terkirim - {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</h3>
    </div>
    @if($details->isEmpty())
        <div class="empty-state">
            <i class="bi bi-journal-x"></i>
            <h4>Tidak ada data</h4>
            <p>Tidak ada transaksi terpotong pada tanggal ini.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        @if($deviceId == 'all')
                        <th>Device</th>
                        @endif
                        <th>Tipe / Template</th>
                        <th>Kontak Tujuan</th>
                        <th>Status Pesan</th>
                        <th>Biaya Terpotong</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sum = 0; @endphp
                    @foreach($details as $d)
                    @php $sum += $d->amount; @endphp
                    <tr>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">{{ $d->time->format('H:i:s') }}</td>
                        @if($deviceId == 'all')
                        <td style="font-weight:600;">{{ $d->device_name }}</td>
                        @endif
                        <td>{{ $d->type }}</td>
                        <td>{{ $d->contact }}</td>
                        <td>
                            @if(in_array($d->status, ['delivered', 'read']))
                                <span class="badge badge-success">{{ ucfirst($d->status) }}</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($d->status) }}</span>
                            @endif
                        </td>
                        <td style="font-weight:700;color:var(--danger);">- Rp {{ number_format($d->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg-secondary);border-top:2px solid var(--border);">
                        <td colspan="{{ $deviceId == 'all' ? '5' : '4' }}" style="text-align:right;font-weight:700;padding:12px 18px;">TOTAL PENGGUNAAN:</td>
                        <td style="font-weight:800;color:var(--danger);padding:12px 18px;">- Rp {{ number_format($sum, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
@endsection
