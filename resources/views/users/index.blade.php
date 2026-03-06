@extends('layouts.app')

@section('title', 'Users')

@section('actions')
<a href="{{ route('users.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah User</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-people-fill" style="color:var(--accent);margin-right:8px;"></i> Daftar Users</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Nama</th><th>Email</th><th>Role</th><th>Devices</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td style="font-weight:600;">{{ $user->name }}</td>
                    <td style="color:var(--text-muted);">{{ $user->email }}</td>
                    <td>
                        @if($user->role === 'superadmin')
                            <span class="badge badge-warning">Superadmin</span>
                        @else
                            <span class="badge badge-info">Admin</span>
                        @endif
                    </td>
                    <td>
                        @foreach($user->devices as $d)
                            <span class="badge badge-success" style="margin:2px;">{{ $d->name }}</span>
                        @endforeach
                        @if($user->devices->isEmpty() && $user->role === 'admin')
                            <span class="badge badge-secondary">Belum ada</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary btn-sm"><i class="bi bi-pencil"></i></a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Yakin hapus user ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
