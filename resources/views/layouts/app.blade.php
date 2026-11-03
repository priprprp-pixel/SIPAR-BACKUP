<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SIPAR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{--ink:#102334;--muted:#718090;--line:#e3e9ef;--canvas:#f2f6f9;--blue:#0868ce;--blue-soft:#e8f2ff;--green:#168361;--amber:#ad6b05;--red:#bd3d4a;--sidebar:#102334}
        *{box-sizing:border-box}body{margin:0;background:var(--canvas);color:var(--ink);font-family:'DM Sans',sans-serif;font-size:14px}.shell{min-height:100vh}.sidebar{position:fixed;inset:0 auto 0 0;width:226px;background:var(--sidebar);color:#fff;padding:22px 14px;display:flex;flex-direction:column;z-index:20}.brand{display:flex;align-items:center;gap:10px;padding:0 10px 26px;color:#fff;text-decoration:none;font-family:Manrope,sans-serif;font-weight:800}.brand-logo{width:36px;height:36px;object-fit:contain;flex:none}.brand small{display:block;color:#9cb0c0;font:400 10px 'DM Sans',sans-serif}.nav-caption{font-size:10px;text-transform:uppercase;color:#758a9d;letter-spacing:.08em;padding:11px 11px 6px}.nav-link{display:flex;align-items:center;gap:11px;color:#c1ceda;text-decoration:none;padding:10px 11px;border-radius:7px;margin:2px 0;font-size:13px}.nav-link:hover,.nav-link.active{background:#203c53;color:white}.nav-link.active{box-shadow:inset 3px 0 #2093ff}.nav-link .bi{font-size:15px;width:18px}.side-user{margin-top:auto;border-top:1px solid #304457;padding:15px 8px 0;color:#dbe3e9;font-size:12px}.side-user small{display:block;color:#91a5b5}.workspace{margin-left:226px;min-height:100vh}.topbar{height:62px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 30px;position:sticky;top:0;z-index:10}.topbar-search{max-width:430px;width:48%;position:relative}.topbar-search .bi{position:absolute;left:12px;top:9px;color:#8493a0}.topbar-search input{padding-left:34px;background:#f7f9fb;border:1px solid var(--line);font-size:12px}.topbar-meta{display:flex;align-items:center;gap:13px;color:var(--muted);font-size:12px}.content{padding:27px 30px 42px;max-width:1480px;margin:auto}.page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px}.page-head h1{font:800 22px Manrope,sans-serif;margin:0 0 4px}.page-head p{margin:0;color:var(--muted);font-size:12px}.surface{background:#fff;border:1px solid var(--line);border-radius:8px}.stat{padding:17px 18px;min-height:105px}.stat-label{color:var(--muted);font-size:11px}.stat-value{font:800 26px Manrope,sans-serif;margin:7px 0 1px}.stat-note{font-size:10px;color:var(--muted)}.stat-icon{height:31px;width:31px;display:grid;place-items:center;border-radius:8px;color:var(--blue);background:var(--blue-soft);float:right}.panel-title{font:700 14px Manrope,sans-serif;margin:0}.panel-subtitle{color:var(--muted);font-size:11px;margin-top:4px}.table{--bs-table-bg:transparent;margin-bottom:0;font-size:12px}.table thead th{font-size:10px;text-transform:uppercase;letter-spacing:.03em;color:#768695;font-weight:600;border-bottom-color:var(--line);padding:11px 12px;white-space:nowrap}.table td{padding:12px;border-color:#edf1f4;vertical-align:middle}.table>:not(caption)>*>*{box-shadow:none}.btn{font-size:12px;font-weight:600;border-radius:6px;padding:8px 13px}.btn-primary{background:var(--blue);border-color:var(--blue)}.btn-light{background:#f5f8fa;border-color:var(--line);color:var(--ink)}.form-control,.form-select{border-color:var(--line);font-size:12px;border-radius:6px;padding:9px 11px}.form-label{font-size:11px;font-weight:600;margin-bottom:6px}.form-text,.text-muted{font-size:11px;color:var(--muted)!important}.badge-status{display:inline-block;border-radius:20px;padding:5px 10px;font-size:11px;line-height:1.2;min-height:26px;font-weight:700;white-space:nowrap}.status-pending,.status-maintenance{color:var(--amber);background:#fff3d9}.status-approved,.status-tersedia,.status-selesai{color:var(--green);background:#e3f5ef}.status-rejected,.status-rusak{color:var(--red);background:#ffeaeb}.status-dipinjam{color:#176cb0;background:#e6f3ff}.equipment-icon{height:38px;width:38px;display:grid;place-items:center;border-radius:9px;background:#eaf3ff;color:#176ec5;font-size:16px;flex:none}.alert{font-size:12px;border-radius:7px}.card-item{padding:16px}.card-item:hover{border-color:#b7d7f3}.modal-content{border:0;border-radius:9px}.qr-box{width:100%;max-width:240px;aspect-ratio:1;background:#f7f9fb;border:1px solid var(--line);display:grid;place-items:center;margin:auto;border-radius:8px}.mobile-bottom{display:none}
        @media(max-width:800px){.sidebar{display:none}.workspace{margin-left:0}.topbar{height:54px;padding:0 15px}.topbar-search{width:65%}.topbar-meta .topbar-name{display:none}.content{padding:20px 15px 28px}.page-head h1{font-size:19px}.page-head{margin-bottom:17px}.table-responsive{border-radius:8px}.content>.row{--bs-gutter-x:12px}.stat{padding:13px;min-height:95px}.stat-value{font-size:22px}.page-head .btn{padding:8px 10px}}
        @media(max-width:575.98px){.topbar{gap:9px;padding:0 10px}.topbar-search{width:auto;flex:1}.topbar-search input{height:30px;padding-left:30px;padding-right:32px;font-size:11px}.topbar-search .bi{left:10px;top:8px}.topbar-search button{top:3px;right:3px;width:26px;height:24px}.topbar-search button .bi{font-size:12px}.topbar-meta{gap:0}.topbar-meta>i,.topbar-meta>span:not(.topbar-profile){display:none}.topbar-profile{display:grid!important}.topbar-avatar{width:30px;height:30px}.topbar-avatar .bi{font-size:15px}.modal-dialog{width:calc(100% - 1rem)!important;margin:.5rem auto!important}.modal-dialog .modal-body{max-height:75vh}}
        @media print{.sidebar,.mobile-nav-toggle,.mobile-drawer,.topbar,.no-print,.page-head .btn{display:none!important}.workspace{margin:0}.content{padding:0;max-width:none}body{background:#fff}.surface{border-color:#ccd3da}.table{font-size:10px}}
    </style>
    <style>@media(min-width:801px){.content{width:100%;max-width:1600px;margin-inline:auto}}</style>
    <style>.sidebar{overflow-y:auto}.sidebar-logout-form{position:sticky;bottom:0;flex:none;margin-top:8px;padding:9px 8px 12px;background:var(--sidebar);border-top:1px solid #304457;z-index:2}.sidebar-logout{display:flex;align-items:center;gap:9px;width:100%;padding:3px 0;border:0;background:none;color:#c1ceda;text-align:left;font:inherit;font-size:12px}.sidebar-logout:hover{color:#fff}</style>
    <style>
        .workspace,.content,.surface{min-width:0}
        .pagination svg{width:1rem;height:1rem;display:block}
        .pagination .page-link{display:flex;align-items:center;justify-content:center;min-width:2.25rem}
        .page-head{flex-wrap:wrap;align-items:flex-start}
        .page-head>div:last-child{max-width:100%;flex-wrap:wrap}
        .topbar-search{min-width:0}
        .topbar-profile{display:flex;align-items:center;gap:13px;color:inherit;text-decoration:none}
        .topbar-profile:hover{color:var(--blue)}
        .topbar-search input{min-width:0;padding-right:38px}
        .modal-dialog{width:calc(100% - 2rem)!important;max-width:560px!important;margin:1.75rem auto!important}
        .modal-dialog .modal-content{max-width:100%;overflow:hidden}
        .modal-dialog .modal-body{max-height:70vh;overflow-y:auto}
        .operational-modal{max-width:560px!important}
        .equipment-modal-dialog{width:calc(100% - 2rem)!important;max-width:620px!important}
        .topbar-search button{position:absolute;top:4px;right:4px;width:34px;height:34px;padding:0;display:grid;place-items:center}
        .topbar-search button .bi{position:static;left:auto;top:auto;color:inherit;font-size:14px;line-height:1}
        .topbar-avatar{display:grid;width:34px;height:34px;flex:none;place-items:center;border-radius:50%;background:#e8f2ff;color:#0868ce}
        .topbar-avatar .bi{font-size:17px;line-height:1}
        .approval-card-header{flex-wrap:wrap}
        .approval-status{align-self:flex-start;display:inline-flex;align-items:center;justify-content:center;flex:none;min-height:26px;padding:5px 10px;line-height:1.2}
        .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .modal-dialog{max-width:calc(100vw - 1rem)}
        .student-edit-dialog{width:calc(100% - 1rem);max-width:480px;margin-right:auto;margin-left:auto}
        .modal-body{max-height:calc(100dvh - 170px);overflow-y:auto}
        .mobile-nav-toggle{display:none;width:36px;height:36px;flex:none;place-items:center;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--ink)}
        .mobile-nav-toggle .bi{font-size:18px}
        .mobile-drawer{--bs-offcanvas-width:min(280px, 85vw);background:var(--sidebar);color:#fff}
        .mobile-drawer .offcanvas-header{padding:18px 14px 10px}
        .mobile-drawer .offcanvas-body{padding:8px 14px 14px}
        .mobile-drawer .brand{padding:0 8px 8px}
        .mobile-drawer .nav-caption{padding-top:13px}
        .mobile-drawer .side-user{margin-top:auto}
        @media(max-width:991.98px){
            .sidebar{display:none}
            .workspace{margin-left:0}
            .mobile-nav-toggle{display:grid}
            .topbar{padding-right:16px;padding-left:16px;gap:10px}
            .topbar-search{width:auto;flex:1}
        }
        @media(max-width:575.98px){
            .topbar{padding-right:12px;padding-left:12px;gap:8px}
            .topbar-meta{gap:8px;white-space:nowrap}
            .content{padding-right:12px;padding-left:12px}
            .page-head{gap:10px}
            .page-head h1{line-height:1.25}
            .page-head>div:last-child{width:100%}
            .page-head>div:last-child .btn{flex:1;min-width:0;white-space:normal}
            .stat{height:100%}
            .modal-dialog{margin:.5rem auto}
        }
    </style>
    @stack('head')
</head>
<body>
@if(auth()->check())
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><img class="brand-logo" src="{{ asset('assets/logo.png') }}" alt="Logo SIPAR"><span>SIPAR<small>Peminjaman Alat RPL</small></span></a>
        <div class="nav-caption">Menu utama</div>
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i>Dashboard</a>
        @if(auth()->user()->role === 'admin')
            <a class="nav-link {{ request()->routeIs('admin.equipment') ? 'active' : '' }}" href="{{ route('admin.equipment') }}"><i class="bi bi-tools"></i>Master Data Alat</a>
            <a class="nav-link {{ request()->routeIs('admin.categories') ? 'active' : '' }}" href="{{ route('admin.categories') }}"><i class="bi bi-collection"></i>Kategori</a>
            <a class="nav-link {{ request()->routeIs('admin.students') ? 'active' : '' }}" href="{{ route('admin.students') }}"><i class="bi bi-people"></i>Data Siswa</a>
            <a class="nav-link {{ request()->routeIs('admin.approvals') ? 'active' : '' }}" href="{{ route('admin.approvals') }}"><i class="bi bi-check2-square"></i>Verifikasi <span class="badge rounded-pill text-bg-warning ms-auto">{{ \App\Models\Peminjaman::where('status', 'Pending')->count() }}</span></a>
            <a class="nav-link {{ request()->routeIs('admin.returns') ? 'active' : '' }}" href="{{ route('admin.returns') }}"><i class="bi bi-upc-scan"></i>Pengembalian</a>
            <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports') }}"><i class="bi bi-file-earmark-bar-graph"></i>Laporan & Export</a>
        @elseif(auth()->user()->role === 'teknisi')
            <a class="nav-link {{ request()->routeIs('technician.handovers') ? 'active' : '' }}" href="{{ route('technician.handovers') }}"><i class="bi bi-box-arrow-in-down"></i>Penyerahan Alat</a>
            <a class="nav-link {{ request()->routeIs('technician.returns') ? 'active' : '' }}" href="{{ route('technician.returns') }}"><i class="bi bi-arrow-return-left"></i>Pengembalian Alat</a>
        @else
            <a class="nav-link {{ request()->routeIs('catalog') ? 'active' : '' }}" href="{{ route('catalog') }}"><i class="bi bi-grid"></i>Katalog Alat</a>
            <a class="nav-link {{ request()->routeIs('loans.mine') ? 'active' : '' }}" href="{{ route('loans.mine') }}"><i class="bi bi-clock-history"></i>Riwayat Peminjaman</a>
        @endif
        <a class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}"><i class="bi bi-person-gear"></i>Profil Saya</a>
        <div class="side-user"><strong>{{ auth()->user()->nama }}</strong><small>{{ auth()->user()->nis_nip }} · {{ ucfirst(auth()->user()->role) }}</small></div>
        <form class="sidebar-logout-form" method="post" action="{{ route('logout') }}">@csrf<button class="sidebar-logout" type="submit"><i class="bi bi-box-arrow-right"></i>Keluar</button></form>
    </aside>
    <div class="offcanvas offcanvas-start mobile-drawer" tabindex="-1" id="mobileNavigation" aria-labelledby="mobileNavigationLabel">
        <div class="offcanvas-header">
            <a class="brand" href="{{ route('dashboard') }}"><img class="brand-logo" src="{{ asset('assets/logo.png') }}" alt="Logo SIPAR"><span>SIPAR<small>Peminjaman Alat RPL</small></span></a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <div class="nav-caption" id="mobileNavigationLabel">Menu utama</div>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i>Dashboard</a>
            @if(auth()->user()->role === 'admin')
                <a class="nav-link {{ request()->routeIs('admin.equipment') ? 'active' : '' }}" href="{{ route('admin.equipment') }}"><i class="bi bi-tools"></i>Master Data Alat</a>
                <a class="nav-link {{ request()->routeIs('admin.categories') ? 'active' : '' }}" href="{{ route('admin.categories') }}"><i class="bi bi-collection"></i>Kategori</a>
                <a class="nav-link {{ request()->routeIs('admin.students') ? 'active' : '' }}" href="{{ route('admin.students') }}"><i class="bi bi-people"></i>Data Siswa</a>
                <a class="nav-link {{ request()->routeIs('admin.approvals') ? 'active' : '' }}" href="{{ route('admin.approvals') }}"><i class="bi bi-check2-square"></i>Verifikasi <span class="badge rounded-pill text-bg-warning ms-auto">{{ \App\Models\Peminjaman::where('status', 'Pending')->count() }}</span></a>
                <a class="nav-link {{ request()->routeIs('admin.returns') ? 'active' : '' }}" href="{{ route('admin.returns') }}"><i class="bi bi-upc-scan"></i>Pengembalian</a>
                <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports') }}"><i class="bi bi-file-earmark-bar-graph"></i>Laporan & Export</a>
            @elseif(auth()->user()->role === 'teknisi')
                <a class="nav-link {{ request()->routeIs('technician.handovers') ? 'active' : '' }}" href="{{ route('technician.handovers') }}"><i class="bi bi-box-arrow-in-down"></i>Penyerahan Alat</a>
                <a class="nav-link {{ request()->routeIs('technician.returns') ? 'active' : '' }}" href="{{ route('technician.returns') }}"><i class="bi bi-arrow-return-left"></i>Pengembalian Alat</a>
            @else
                <a class="nav-link {{ request()->routeIs('catalog') ? 'active' : '' }}" href="{{ route('catalog') }}"><i class="bi bi-grid"></i>Katalog Alat</a>
                <a class="nav-link {{ request()->routeIs('loans.mine') ? 'active' : '' }}" href="{{ route('loans.mine') }}"><i class="bi bi-clock-history"></i>Riwayat Peminjaman</a>
            @endif
            <a class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}"><i class="bi bi-person-gear"></i>Profil Saya</a>
            <div class="side-user"><strong>{{ auth()->user()->nama }}</strong><small>{{ auth()->user()->nis_nip }} · {{ ucfirst(auth()->user()->role) }}</small></div>
            <form class="sidebar-logout-form" method="post" action="{{ route('logout') }}">@csrf<button class="sidebar-logout" type="submit"><i class="bi bi-box-arrow-right"></i>Keluar</button></form>
        </div>
    </div>
    <main class="workspace">
        <header class="topbar">
            <button class="mobile-nav-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavigation" aria-controls="mobileNavigation" aria-label="Buka navigasi"><i class="bi bi-list"></i></button>
            <form class="topbar-search" method="get" action="{{ request()->routeIs('catalog') ? route('catalog') : route('search') }}" role="search">
                <input class="form-control form-control-sm" type="search" name="q" value="{{ request('q') }}" placeholder="{{ request()->routeIs('catalog') ? 'Cari nama atau kode alat...' : 'Cari alat, transaksi, atau siswa...' }}" aria-label="Cari">
                <button class="btn btn-primary" type="submit" aria-label="Cari"><i class="bi bi-search"></i></button>
            </form>
            <div class="topbar-meta"><i class="bi bi-calendar3"></i><span>{{ now()->translatedFormat('d M Y') }}</span><a class="topbar-profile" href="{{ route('profile') }}" aria-label="Buka profil saya"><span class="topbar-name">{{ auth()->user()->nama }}</span><span class="topbar-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span></a></div>
        </header>
        <div class="content">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>@endif
            @if($errors->any())<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>@endif
            @yield('content')
        </div>
    </main>
</div>
@else
    @yield('content')
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
