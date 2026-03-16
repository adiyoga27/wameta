<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — WA Meta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg-primary: #0a0f1a;
            --bg-secondary: #111827;
            --bg-card: rgba(17, 24, 39, 0.7);
            --bg-glass: rgba(255,255,255,0.03);
            --border: rgba(255,255,255,0.06);
            --border-light: rgba(255,255,255,0.1);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --accent: #25D366;
            --accent-soft: rgba(37,211,102,0.12);
            --accent-hover: #1fb855;
            --danger: #ef4444;
            --danger-soft: rgba(239,68,68,0.12);
            --warning: #f59e0b;
            --warning-soft: rgba(245,158,11,0.12);
            --info: #3b82f6;
            --info-soft: rgba(59,130,246,0.12);
            --purple: #a855f7;
            --purple-soft: rgba(168,85,247,0.12);
            --sidebar-w: 260px;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 4px 24px rgba(0,0,0,0.3);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); color: var(--text-primary); min-height: 100vh; display: flex; }
        a { color: var(--accent); text-decoration: none; transition: color 0.2s; }
        a:hover { color: var(--accent-hover); }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-w); min-height: 100vh; background: var(--bg-secondary);
            border-right: 1px solid var(--border); display: flex; flex-direction: column;
            position: fixed; left: 0; top: 0; z-index: 100; transition: transform 0.3s ease;
        }
        .sidebar-brand {
            padding: 24px 20px; display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-brand .logo {
            width: 40px; height: 40px; background: linear-gradient(135deg, var(--accent), #128c50);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: white; font-weight: 700;
        }
        .sidebar-brand h1 { font-size: 18px; font-weight: 700; color: var(--text-primary); }
        .sidebar-brand span { font-size: 11px; color: var(--text-muted); font-weight: 400; }

        .sidebar-nav { padding: 16px 12px; flex: 1; overflow-y: auto; }
        .nav-section { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); padding: 12px 12px 6px; font-weight: 600; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 10px 14px;
            border-radius: var(--radius-sm); color: var(--text-secondary);
            transition: all 0.2s; font-size: 14px; font-weight: 500; margin-bottom: 2px;
        }
        .nav-item:hover { background: var(--bg-glass); color: var(--text-primary); }
        .nav-item.active { background: var(--accent-soft); color: var(--accent); }
        .nav-item i { font-size: 18px; width: 22px; text-align: center; }
        .nav-badge {
            margin-left: auto; background: var(--accent); color: #000; font-size: 10px;
            font-weight: 700; padding: 2px 8px; border-radius: 10px;
        }

        .sidebar-footer { padding: 16px 12px; border-top: 1px solid var(--border); }
        .user-info { display: flex; align-items: center; gap: 10px; padding: 8px 12px; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 8px;
            background: linear-gradient(135deg, var(--accent), var(--info));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; color: white;
        }
        .user-name { font-size: 13px; font-weight: 600; }
        .user-role { font-size: 11px; color: var(--text-muted); text-transform: capitalize; }

        /* Main Content */
        .main { flex: 1; margin-left: var(--sidebar-w); min-height: 100vh; }
        .topbar {
            padding: 16px 32px; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); background: var(--bg-secondary);
            position: sticky; top: 0; z-index: 50; backdrop-filter: blur(12px);
        }
        .topbar h2 { font-size: 20px; font-weight: 700; }
        .topbar-actions { display: flex; align-items: center; gap: 12px; }

        .page-content { padding: 28px 32px; }

        /* Cards */
        .card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 24px; backdrop-filter: blur(12px);
        }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h3 { font-size: 16px; font-weight: 600; }

        /* Stat Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .stat-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px; backdrop-filter: blur(12px);
            transition: transform 0.2s, border-color 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); border-color: var(--border-light); }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 14px;
        }
        .stat-icon.green { background: var(--accent-soft); color: var(--accent); }
        .stat-icon.blue { background: var(--info-soft); color: var(--info); }
        .stat-icon.orange { background: var(--warning-soft); color: var(--warning); }
        .stat-icon.red { background: var(--danger-soft); color: var(--danger); }
        .stat-icon.purple { background: var(--purple-soft); color: var(--purple); }
        .stat-value { font-size: 28px; font-weight: 800; margin-bottom: 4px; }
        .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }

        /* Tables */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            text-align: left; padding: 12px 16px; font-size: 11px;
            text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);
            border-bottom: 1px solid var(--border); font-weight: 600;
        }
        tbody td { padding: 14px 16px; border-bottom: 1px solid var(--border); font-size: 14px; }
        tbody tr { transition: background 0.15s; }
        tbody tr:hover { background: var(--bg-glass); }
        tbody tr:last-child td { border-bottom: none; }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 6px; font-size: 11px;
            font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-success { background: var(--accent-soft); color: var(--accent); }
        .badge-danger { background: var(--danger-soft); color: var(--danger); }
        .badge-warning { background: var(--warning-soft); color: var(--warning); }
        .badge-info { background: var(--info-soft); color: var(--info); }
        .badge-secondary { background: rgba(255,255,255,0.06); color: var(--text-secondary); }
        .badge-purple { background: var(--purple-soft); color: var(--purple); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: var(--radius-sm); font-size: 13px;
            font-weight: 600; border: none; cursor: pointer; transition: all 0.2s;
            font-family: 'Inter', sans-serif; text-decoration: none;
        }
        .btn-primary { background: var(--accent); color: #000; }
        .btn-primary:hover { background: var(--accent-hover); color: #000; transform: translateY(-1px); }
        .btn-secondary { background: rgba(255,255,255,0.06); color: var(--text-primary); border: 1px solid var(--border); }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); color: var(--text-primary); }
        .btn-danger { background: var(--danger-soft); color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-icon { padding: 8px; border-radius: 8px; }

        /* Forms */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; }
        .form-control {
            width: 100%; padding: 10px 14px; background: var(--bg-primary);
            border: 1px solid var(--border); border-radius: var(--radius-sm);
            color: var(--text-primary); font-size: 14px; font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }
        .form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
        .form-control::placeholder { color: var(--text-muted); }
        select.form-control { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%239ca3af' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px; }
        textarea.form-control { min-height: 100px; resize: vertical; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        .form-hint { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        .form-error { font-size: 12px; color: var(--danger); margin-top: 4px; }

        /* Alerts */
        .alert {
            padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px; font-size: 14px;
            animation: slideIn 0.3s ease;
        }
        .alert-success { background: var(--accent-soft); color: var(--accent); border: 1px solid rgba(37,211,102,0.2); }
        .alert-danger { background: var(--danger-soft); color: var(--danger); border: 1px solid rgba(239,68,68,0.2); }
        .alert-warning { background: var(--warning-soft); color: var(--warning); border: 1px solid rgba(245,158,11,0.2); }
        .alert-info { background: var(--info-soft); color: var(--info); border: 1px solid rgba(59,130,246,0.2); }

        /* Phone badge */
        .phone-tag { background: var(--bg-primary); padding: 4px 10px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 13px; }

        /* Checkbox switch */
        .checkbox-list { display: flex; flex-wrap: wrap; gap: 10px; }
        .checkbox-item {
            display: flex; align-items: center; gap: 8px; padding: 8px 14px;
            background: var(--bg-primary); border: 1px solid var(--border);
            border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s;
        }
        .checkbox-item:hover { border-color: var(--accent); }
        .checkbox-item input[type="checkbox"] { accent-color: var(--accent); }
        .checkbox-item.checked { background: var(--accent-soft); border-color: var(--accent); }

        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 4px; margin-top: 20px; }
        .pagination a, .pagination span {
            padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 500;
            display: inline-flex; align-items: center; transition: all 0.2s;
        }
        .pagination a { background: var(--bg-card); border: 1px solid var(--border); color: var(--text-secondary); }
        .pagination a:hover { border-color: var(--accent); color: var(--accent); }
        .pagination .active { background: var(--accent); color: #000; font-weight: 700; }
        .pagination .disabled { opacity: 0.3; pointer-events: none; }

        /* Mobile toggle */
        .sidebar-toggle {
            display: none; background: var(--bg-card); border: 1px solid var(--border);
            color: var(--text-primary); padding: 8px 12px; border-radius: var(--radius-sm);
            cursor: pointer; font-size: 18px;
        }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--text-muted);
        }
        .empty-state i { font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.4; }
        .empty-state h4 { font-size: 16px; margin-bottom: 8px; color: var(--text-secondary); }
        .empty-state p { font-size: 13px; }

        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            z-index: 200; justify-content: center; align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--bg-secondary); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 28px; width: 90%; max-width: 500px;
            max-height: 90vh; overflow-y: auto; animation: slideIn 0.3s ease;
        }
        .modal h3 { margin-bottom: 20px; font-size: 18px; }

        /* Animations */
        @keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: var(--shadow); }
            .sidebar-toggle { display: flex; }
            .sidebar-overlay.open { display: block; }
            .main { margin-left: 0; }
            .topbar { padding: 14px 16px; }
            .page-content { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><i class="bi bi-whatsapp"></i></div>
            <div>
                <h1>WA Meta</h1>
                <span>WhatsApp Business API</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">Menu</div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>

            @if(auth()->user()->isSuperAdmin())
            <div class="nav-section">Admin</div>
            <a href="{{ route('devices.index') }}" class="nav-item {{ request()->routeIs('devices.*') ? 'active' : '' }}">
                <i class="bi bi-phone-fill"></i> Devices
            </a>
            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Users
            </a>
            @endif

            <div class="nav-section">WhatsApp</div>
            <a href="{{ route('templates.index') }}" class="nav-item {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text-fill"></i> Templates
            </a>
            <a href="{{ route('broadcasts.index') }}" class="nav-item {{ request()->routeIs('broadcasts.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone-fill"></i> Broadcast
            </a>
            <a href="{{ route('contacts.index') }}" class="nav-item {{ request()->routeIs('contacts.*') ? 'active' : '' }}">
                <i class="bi bi-person-lines-fill"></i> Kontak
            </a>
            <a href="{{ route('topups.index') }}" class="nav-item {{ request()->routeIs('topups.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Saldo
            </a>
            <a href="{{ route('messages.index') }}" class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                <i class="bi bi-chat-dots-fill"></i> Pesan Masuk
            </a>

            @if(auth()->user()->isSuperAdmin())
            <div class="nav-section">Developer</div>

            <a href="{{ route('webhook-logs.index') }}" class="nav-item {{ request()->routeIs('webhook-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-code"></i> Webhook Logs
            </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div>
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">{{ auth()->user()->role }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px;">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="main">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
                <h2>@yield('title', 'Dashboard')</h2>
            </div>
            <div class="topbar-actions">
                @yield('actions')
                @stack('topbar_actions')
            </div>
        </header>

        <div class="page-content">
            @if(session('success'))
                <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('warning') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle-fill"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
        }
    </script>
    @yield('scripts')
</body>
</html>
