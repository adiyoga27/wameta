@extends('layouts.app')

@section('title', 'Broadcasts')

@section('actions')
<div style="display:flex;gap:10px;align-items:center;">
    <form method="GET" action="{{ route('broadcasts.index') }}" style="display:flex;gap:8px;align-items:center;">
        <select name="device_id" class="form-control form-control-sm" style="width:200px;" onchange="this.form.submit()">
            @foreach($devices as $d)
                <option value="{{ $d->id }}" {{ $deviceId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('broadcasts.create', ['device_id' => $deviceId]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Buat Broadcast</a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <h3 style="margin:0;"><i class="bi bi-megaphone-fill" style="color:var(--accent);margin-right:8px;"></i> Daftar Broadcast</h3>
        <form method="GET" action="{{ route('broadcasts.index') }}" style="margin:0;">
            <input type="hidden" name="device_id" value="{{ $deviceId }}">
            <div style="position:relative; width:260px; max-width:100%;">
                <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:14px;"></i>
                <input type="text" name="search" class="form-control" placeholder="Cari nama / template..." value="{{ request('search') }}" style="width:100%; border-radius:20px; padding:8px 15px 8px 35px; border:1px solid var(--border); background:var(--bg-secondary); font-size:13px; outline:none; box-shadow:none; transition:border-color 0.2s;">
            </div>
        </form>
    </div>
    @if($broadcasts->isEmpty())
        <div class="empty-state">
            <i class="bi bi-megaphone"></i>
            <h4>Belum ada broadcast</h4>
            <p>Buat broadcast marketing pertama Anda</p>
        </div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Nama</th><th>Template</th><th>Total</th><th>Terkirim</th><th>Diterima</th><th>Gagal</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($broadcasts as $bc)
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $bc->name }}</div>
                            <div style="font-size:12px;color:var(--text-muted);">{{ $bc->created_at->format('d M Y H:i') }}</div>
                        </td>
                        <td><span class="badge badge-purple">{{ $bc->messageTemplate->name ?? '-' }}</span></td>
                        <td>{{ $bc->total }}</td>
                        <td style="color:var(--accent);">{{ $bc->sent }}</td>
                        <td style="color:var(--info);">{{ $bc->delivered }}</td>
                        <td style="color:var(--danger);">{{ $bc->failed }}</td>
                        <td>
                            @switch($bc->status)
                                @case('completed') <span class="badge badge-success">Selesai</span> @break
                                @case('sending') <span class="badge badge-info">Mengirim...</span> @break
                                @case('draft') <span class="badge badge-secondary">Draft</span> @break
                                @default <span class="badge badge-danger">Gagal</span>
                            @endswitch
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="{{ route('broadcasts.show', $bc) }}" class="btn btn-secondary btn-sm" title="Lihat Detail"><i class="bi bi-eye"></i></a>
                                <form action="{{ route('broadcasts.destroy', $bc) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus broadcast ini beserta seluruh daftar kontaknya?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
