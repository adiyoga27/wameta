@extends('layouts.app')

@section('title', 'Detail Device: ' . $device->name)

@section('actions')
<div style="display:flex;gap:8px;">
    <a href="{{ route('devices.edit', $device) }}" class="btn btn-secondary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    <a href="{{ route('devices.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
@endsection

@section('content')

<!-- Local Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div class="stat-value">{{ $localStats['templates_total'] }}</div>
        <div class="stat-label">Total Templates</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-value">{{ $localStats['templates_approved'] }}</div>
        <div class="stat-label">Approved</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-megaphone-fill"></i></div>
        <div class="stat-value">{{ $localStats['broadcasts'] }}</div>
        <div class="stat-label">Broadcasts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-send-check-fill"></i></div>
        <div class="stat-value">{{ $localStats['total_sent'] }}</div>
        <div class="stat-label">Terkirim</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-check2-all"></i></div>
        <div class="stat-value">{{ $localStats['total_delivered'] }}</div>
        <div class="stat-label">Diterima</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-chat-dots-fill"></i></div>
        <div class="stat-value">{{ $localStats['messages_received'] }}</div>
        <div class="stat-label">Pesan Masuk</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <!-- Phone Number Info from Meta -->
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-telephone-fill" style="color:var(--accent);margin-right:8px;"></i> Info Nomor Telepon (Meta)</h3>
        </div>
        @if(!$phoneInfo)
            <div class="alert alert-warning" style="margin-bottom:0;"><i class="bi bi-exclamation-triangle-fill"></i> Phone Number ID atau Access Token belum diisi.</div>
        @elseif(isset($phoneInfo['_error']))
            <div class="alert alert-danger" style="margin-bottom:0;"><i class="bi bi-x-circle-fill"></i> {{ $phoneInfo['_error'] }}</div>
        @else
            <table style="width:100%;">
                <tbody>
                    @if(isset($phoneInfo['display_phone_number']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);width:45%;">Nomor Telepon</td><td style="padding:10px 0;font-weight:600;">{{ $phoneInfo['display_phone_number'] }}</td></tr>
                    @endif
                    @if(isset($phoneInfo['verified_name']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Nama Terverifikasi</td><td style="padding:10px 0;font-weight:600;">{{ $phoneInfo['verified_name'] }}</td></tr>
                    @endif
                    @if(isset($phoneInfo['quality_rating']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Quality Rating</td><td style="padding:10px 0;">
                        @if($phoneInfo['quality_rating'] === 'GREEN')
                            <span class="badge badge-success"><i class="bi bi-star-fill"></i> GREEN</span>
                        @elseif($phoneInfo['quality_rating'] === 'YELLOW')
                            <span class="badge badge-warning"><i class="bi bi-star-half"></i> YELLOW</span>
                        @else
                            <span class="badge badge-danger"><i class="bi bi-star"></i> {{ $phoneInfo['quality_rating'] }}</span>
                        @endif
                    </td></tr>
                    @endif
                    @if(isset($phoneInfo['platform_type']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Platform</td><td style="padding:10px 0;"><span class="badge badge-info">{{ $phoneInfo['platform_type'] }}</span></td></tr>
                    @endif
                    @if(isset($phoneInfo['name_status']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Name Status</td><td style="padding:10px 0;">
                        @if($phoneInfo['name_status'] === 'APPROVED')
                            <span class="badge badge-success">{{ $phoneInfo['name_status'] }}</span>
                        @else
                            <span class="badge badge-warning">{{ $phoneInfo['name_status'] }}</span>
                        @endif
                    </td></tr>
                    @endif
                    @if(isset($phoneInfo['code_verification_status']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Verifikasi Kode</td><td style="padding:10px 0;"><span class="badge badge-info">{{ $phoneInfo['code_verification_status'] }}</span></td></tr>
                    @endif
                    @if(isset($phoneInfo['is_official_business_account']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Official Business</td><td style="padding:10px 0;">
                        @if($phoneInfo['is_official_business_account'])
                            <span class="badge badge-success"><i class="bi bi-patch-check-fill"></i> Ya</span>
                        @else
                            <span class="badge badge-secondary">Tidak</span>
                        @endif
                    </td></tr>
                    @endif
                    @if(isset($phoneInfo['throughput']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Throughput</td><td style="padding:10px 0;">{{ $phoneInfo['throughput']['level'] ?? json_encode($phoneInfo['throughput']) }}</td></tr>
                    @endif
                    @if(isset($phoneInfo['account_mode']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Account Mode</td><td style="padding:10px 0;"><span class="badge badge-info">{{ $phoneInfo['account_mode'] }}</span></td></tr>
                    @endif
                    @if(isset($phoneInfo['last_onboarded_time']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Terakhir Onboarded</td><td style="padding:10px 0;font-size:13px;">{{ $phoneInfo['last_onboarded_time'] }}</td></tr>
                    @endif
                    @if(isset($phoneInfo['id']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Phone Number ID</td><td style="padding:10px 0;"><span class="phone-tag">{{ $phoneInfo['id'] }}</span></td></tr>
                    @endif
                </tbody>
            </table>
        @endif
    </div>

    <!-- WABA Info from Meta -->
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-building" style="color:var(--info);margin-right:8px;"></i> Info WABA (Meta)</h3>
        </div>
        @if(!$wabaInfo)
            <div class="alert alert-warning" style="margin-bottom:0;"><i class="bi bi-exclamation-triangle-fill"></i> WABA ID atau Access Token belum diisi.</div>
        @elseif(isset($wabaInfo['_error']))
            <div class="alert alert-danger" style="margin-bottom:0;"><i class="bi bi-x-circle-fill"></i> {{ $wabaInfo['_error'] }}</div>
        @else
            <table style="width:100%;">
                <tbody>
                    @if(isset($wabaInfo['name']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);width:45%;">Nama WABA</td><td style="padding:10px 0;font-weight:600;">{{ $wabaInfo['name'] }}</td></tr>
                    @endif
                    @if(isset($wabaInfo['id']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">WABA ID</td><td style="padding:10px 0;"><span class="phone-tag">{{ $wabaInfo['id'] }}</span></td></tr>
                    @endif
                    @if(isset($wabaInfo['currency']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Mata Uang</td><td style="padding:10px 0;"><span class="badge badge-info">{{ $wabaInfo['currency'] }}</span></td></tr>
                    @endif
                    @if(isset($wabaInfo['timezone_id']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Timezone</td><td style="padding:10px 0;">{{ $wabaInfo['timezone_id'] }}</td></tr>
                    @endif
                    @if(isset($wabaInfo['account_review_status']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Account Review</td><td style="padding:10px 0;">
                        @if($wabaInfo['account_review_status'] === 'APPROVED')
                            <span class="badge badge-success">{{ $wabaInfo['account_review_status'] }}</span>
                        @else
                            <span class="badge badge-warning">{{ $wabaInfo['account_review_status'] }}</span>
                        @endif
                    </td></tr>
                    @endif
                    @if(isset($wabaInfo['business_verification_status']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Business Verification</td><td style="padding:10px 0;">
                        @if($wabaInfo['business_verification_status'] === 'verified')
                            <span class="badge badge-success"><i class="bi bi-patch-check-fill"></i> Verified</span>
                        @else
                            <span class="badge badge-warning">{{ $wabaInfo['business_verification_status'] }}</span>
                        @endif
                    </td></tr>
                    @endif
                    @if(isset($wabaInfo['ownership_type']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Ownership Type</td><td style="padding:10px 0;">{{ $wabaInfo['ownership_type'] }}</td></tr>
                    @endif
                    @if(isset($wabaInfo['message_template_namespace']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Template Namespace</td><td style="padding:10px 0;font-size:12px;word-break:break-all;">{{ $wabaInfo['message_template_namespace'] }}</td></tr>
                    @endif
                    @if(isset($wabaInfo['primary_funding_id']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Funding ID</td><td style="padding:10px 0;font-size:12px;">{{ $wabaInfo['primary_funding_id'] }}</td></tr>
                    @endif
                    @if(isset($wabaInfo['on_behalf_of_business_info']))
                    <tr><td style="padding:10px 0;color:var(--text-muted);">Business Info</td><td style="padding:10px 0;">
                        @if(is_array($wabaInfo['on_behalf_of_business_info']))
                            <div style="font-weight:600;">{{ $wabaInfo['on_behalf_of_business_info']['name'] ?? '' }}</div>
                            <div style="font-size:12px;color:var(--text-muted);">ID: {{ $wabaInfo['on_behalf_of_business_info']['id'] ?? '' }}</div>
                        @else
                            {{ $wabaInfo['on_behalf_of_business_info'] }}
                        @endif
                    </td></tr>
                    @endif
                </tbody>
            </table>
        @endif
    </div>
</div>

<!-- Business Profile -->
@if($businessProfile)
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3><i class="bi bi-person-badge-fill" style="color:var(--accent);margin-right:8px;"></i> Business Profile</h3>
    </div>
    <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start;">
        @if(isset($businessProfile['profile_picture_url']))
        <div>
            <img src="{{ $businessProfile['profile_picture_url'] }}" alt="Profile" style="width:80px;height:80px;border-radius:12px;object-fit:cover;border:2px solid var(--border);">
        </div>
        @endif
        <table style="width:100%;">
            <tbody>
                @if(isset($businessProfile['about']))
                <tr><td style="padding:8px 0;color:var(--text-muted);width:30%;">About</td><td style="padding:8px 0;">{{ $businessProfile['about'] }}</td></tr>
                @endif
                @if(isset($businessProfile['description']))
                <tr><td style="padding:8px 0;color:var(--text-muted);">Deskripsi</td><td style="padding:8px 0;">{{ $businessProfile['description'] }}</td></tr>
                @endif
                @if(isset($businessProfile['address']))
                <tr><td style="padding:8px 0;color:var(--text-muted);">Alamat</td><td style="padding:8px 0;">{{ $businessProfile['address'] }}</td></tr>
                @endif
                @if(isset($businessProfile['email']))
                <tr><td style="padding:8px 0;color:var(--text-muted);">Email</td><td style="padding:8px 0;">{{ $businessProfile['email'] }}</td></tr>
                @endif
                @if(isset($businessProfile['vertical']))
                <tr><td style="padding:8px 0;color:var(--text-muted);">Industri</td><td style="padding:8px 0;"><span class="badge badge-info">{{ $businessProfile['vertical'] }}</span></td></tr>
                @endif
                @if(isset($businessProfile['websites']))
                <tr><td style="padding:8px 0;color:var(--text-muted);">Websites</td><td style="padding:8px 0;">
                    @foreach($businessProfile['websites'] as $url)
                        <a href="{{ $url }}" target="_blank" style="display:block;font-size:13px;">{{ $url }}</a>
                    @endforeach
                </td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Credential Info -->
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3><i class="bi bi-key-fill" style="color:var(--warning);margin-right:8px;"></i> Credential Lokal</h3>
    </div>
    <table style="width:100%;">
        <tbody>
            <tr><td style="padding:10px 0;color:var(--text-muted);width:30%;">Nama Device</td><td style="padding:10px 0;font-weight:600;">{{ $device->name }}</td></tr>
            <tr><td style="padding:10px 0;color:var(--text-muted);">WABA ID</td><td style="padding:10px 0;"><span class="phone-tag">{{ $device->waba_id ?: '-' }}</span></td></tr>
            <tr><td style="padding:10px 0;color:var(--text-muted);">Phone Number ID</td><td style="padding:10px 0;"><span class="phone-tag">{{ $device->phone_number_id ?: '-' }}</span></td></tr>
            <tr><td style="padding:10px 0;color:var(--text-muted);">App ID</td><td style="padding:10px 0;">{{ $device->app_id ?: '-' }}</td></tr>
            <tr><td style="padding:10px 0;color:var(--text-muted);">Access Token</td><td style="padding:10px 0;font-size:12px;word-break:break-all;max-width:600px;">{{ $device->access_token ? Str::limit($device->access_token, 50) : '-' }}</td></tr>
            <tr><td style="padding:10px 0;color:var(--text-muted);">Webhook Verify Token</td><td style="padding:10px 0;">
                <div style="display:flex;gap:8px;align-items:center;">
                    <code style="color:var(--accent);font-size:12px;">{{ $device->webhook_verify_token }}</code>
                    <button onclick="navigator.clipboard.writeText('{{ $device->webhook_verify_token }}')" class="btn btn-secondary btn-sm" style="padding:4px 8px;"><i class="bi bi-clipboard"></i></button>
                </div>
            </td></tr>
            <tr><td style="padding:10px 0;color:var(--text-muted);">Users</td><td style="padding:10px 0;">
                @forelse($device->users as $u)
                    <span class="badge badge-info" style="margin-right:4px;">{{ $u->name }}</span>
                @empty
                    <span class="badge badge-secondary">Tidak ada</span>
                @endforelse
            </td></tr>
        </tbody>
    </table>
</div>

<!-- Note about balance -->
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3><i class="bi bi-info-circle-fill" style="color:var(--info);margin-right:8px;"></i> Tentang Saldo / Billing</h3>
    </div>
    <div style="font-size:14px;color:var(--text-secondary);line-height:1.8;">
        <p><i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);"></i> WhatsApp Cloud API <strong>tidak menyediakan endpoint langsung</strong> untuk melihat saldo/credit Meta secara real-time melalui API.</p>
        <p style="margin-top:10px;">Untuk melihat saldo dan billing:</p>
        <ul style="margin-top:8px;padding-left:20px;">
            <li>Buka <a href="https://business.facebook.com/billing" target="_blank">Meta Business Suite → Billing</a></li>
            <li>Atau buka <a href="https://developers.facebook.com" target="_blank">Meta Developer Console</a> → App Dashboard → WhatsApp</li>
        </ul>
        <p style="margin-top:10px;font-size:13px;color:var(--text-muted);">Info billing dikelola langsung di Meta Business Manager dan bersifat per-akun bisnis, bukan per-WABA.</p>
    </div>
</div>
@endsection
