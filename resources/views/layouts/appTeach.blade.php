{{-- resources/views/layouts/appTeach.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Reviso')</title>

    <link href="{{ asset('assets/img/brand/favicon.png') }}" rel="icon" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 286px;
            --sidebar-bg: #245E55;
            --sidebar-border: rgba(255,255,255,0.10);
            --nav-text: rgba(255,255,255,0.55);
            --nav-text-hover: rgba(255,255,255,0.95);
            --nav-active-bg: rgba(255,255,255,0.12);
            --nav-active-text: #fff;
            --nav-active-bar: #EAC119;
            --content-bg: #EAE4DA;
            --font: 'DM Sans', sans-serif;
            --font-serif: 'DM Sans', sans-serif;
        }

        body { font-family: var(--font); font-size: 17px; line-height: 1.55; background: var(--content-bg); color: #2D2D2B; display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .rv-sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            border-right: 1px solid var(--sidebar-border);
            transition: transform 0.25s ease;
        }

        .rv-sidebar-brand {
            padding: 26px 24px 20px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .rv-sidebar-brand img { height: 48px; width: auto; }

        .rv-sidebar-brand-name {
            font-family: var(--font-serif);
            font-size: 27px;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .rv-nav { flex: 1; padding: 16px 13px; overflow-y: auto; }
        .rv-nav::-webkit-scrollbar { width: 0; }

        .rv-nav-section {
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.25);
            padding: 16px 13px 8px;
        }

        .rv-nav-item {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 13px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--nav-text);
            font-size: 18px;
            transition: background 0.15s, color 0.15s;
            position: relative;
            margin-bottom: 3px;
        }

        .rv-nav-item:hover { background: rgba(255,255,255,0.06); color: var(--nav-text-hover); }

        .rv-nav-item.active {
            background: var(--nav-active-bg);
            color: var(--nav-active-text);
            font-weight: 500;
        }

        .rv-nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 22px;
            background: var(--nav-active-bar);
            border-radius: 0 3px 3px 0;
        }

        .rv-nav-icon { width: 22px; text-align: center; font-size: 16px; opacity: 0.7; flex-shrink: 0; }
        .rv-nav-item.active .rv-nav-icon, .rv-nav-item:hover .rv-nav-icon { opacity: 1; }

        .rv-sidebar-footer { padding: 18px 13px; border-top: 1px solid var(--sidebar-border); }

        .rv-user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 13px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
        }

        .rv-user-pill:hover { background: rgba(255,255,255,0.06); }

        .rv-user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.15);
            object-fit: cover;
            flex-shrink: 0;
        }

        .rv-user-name { font-size: 16px; color: rgba(255,255,255,0.8); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rv-user-role { font-size: 14px; color: rgba(255,255,255,0.35); }

        .rv-signout-btn {
            width: 100%; background: none; border: none; cursor: pointer;
            padding: 10px 13px; border-radius: 10px;
            font-family: var(--font); font-size: 15px;
            color: rgba(255,255,255,0.7); text-align: left;
            transition: color 0.15s, background 0.15s;
            margin-top: 6px;
        }
  
        .rv-signout-btn:hover { color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.05); }
 
        /* ── Main ── */
        .rv-main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ── Topbar ── */
        .rv-topbar {
            background: #F7F2E9;
            border-bottom: 1px solid #DDD8CF;
            padding: 0 34px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .rv-topbar-title { font-family: var(--font-serif); font-size: 30px; color: #2D2D2B; font-weight: 500; letter-spacing: -0.02em; }

        .rv-topbar-right { display: flex; align-items: center; gap: 10px; }

        /* ── Content ── */
        .rv-content { flex: 1; padding: 36px; }
        /* ── Shared component styles (used across all pages) ── */
        .rv-btn {
            height: 46px; padding: 0 20px; border-radius: 10px;
            font-family: var(--font); font-size: 16px; font-weight: 500;
            cursor: pointer; transition: background 0.15s, transform 0.1s, border-color 0.15s;
            border: 1px solid transparent;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .rv-btn:active { transform: scale(0.98); }
        .rv-btn-primary { background: #ED773C; color: #fff; border-color: #ED773C; }
        .rv-btn-primary:hover { background: #d4602a; border-color: #d4602a; }
        .rv-btn-secondary { background: #F7F2E9; color: #5a5550; border-color: #DDD8CF; }
        .rv-btn-secondary:hover { border-color: #b5afa5; color: #2D2D2B; }
        .rv-btn-danger { background: #F7F2E9; color: #C63F3E; border-color: #e8b8b7; }
        .rv-btn-danger:hover { background: #f5e3e3; }
        .rv-btn-success { background: #245E55; color: #fff; border-color: #245E55; }
        .rv-btn-success:hover { background: #1a4840; border-color: #1a4840; }

        .rv-label { display: block; font-size: 14px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase; color: #8a8580; margin-bottom: 8px; }
        .rv-input, .rv-select, .rv-textarea {
            width: 100%; padding: 12px 14px; border: 1px solid #DDD8CF; border-radius: 10px;
            font-family: var(--font); font-size: 17px; color: #2D2D2B; background: #F7F2E9; outline: none;
            transition: border-color 0.15s, box-shadow 0.15s; appearance: none;
        }
        .rv-textarea { resize: vertical; min-height: 96px; line-height: 1.55; }
        .rv-input:focus, .rv-select:focus, .rv-textarea:focus { border-color: #245E55; box-shadow: 0 0 0 3px rgba(36,94,85,0.08); }
        .rv-input::placeholder, .rv-textarea::placeholder { color: #B5AFA5; }
        .rv-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23aaa' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
        }
        .rv-form-group { margin-bottom: 22px; }

        .rv-alert { padding: 13px 16px; border-radius: 10px; font-size: 16px; margin-bottom: 18px; }
        .rv-alert-success { background: #d4e8e5; color: #1a4840; }
        .rv-alert-danger  { background: #f5e3e3; color: #9e2f2e; }

        /* ── Drawer ── */
        .rv-overlay {
            display: none;
            position: fixed !important;
            inset: 0 !important;
            background: rgba(0, 0, 0, 0.45) !important;
            z-index: 99998 !important;
            backdrop-filter: blur(2px);
            pointer-events: none !important;
        }
        .rv-overlay.open {
            display: block !important;
            pointer-events: auto !important;
        }

        .rv-drawer {
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 480px !important;
            max-width: 95vw !important;
            height: 100vh !important;
            max-height: 100vh !important;
            background: #FAF7F2 !important;
            z-index: 99999 !important;
            display: flex !important;
            flex-direction: column !important;
            transform: translateX(110%) !important;
            transition: transform 0.25s cubic-bezier(0.4,0,0.2,1) !important;
            box-shadow: -8px 0 36px rgba(0,0,0,0.18) !important;
            pointer-events: none !important;
        }
        .rv-drawer.open {
            transform: translateX(0) !important;
            pointer-events: auto !important;
        }

        .rv-drawer-head {
            padding: 24px 28px 18px; border-bottom: 1px solid #DDD8CF;
            display: flex; align-items: flex-start; justify-content: space-between; flex-shrink: 0;
        }
        .rv-drawer-title { font-family: var(--font-serif); font-size: 24px; color: #2D2D2B; font-weight: 500; }
        .rv-drawer-subtitle { font-size: 15px; color: #8a8580; margin-top: 4px; }
        .rv-drawer-close {
            width: 38px; height: 38px; border: 1px solid #DDD8CF; background: #F7F2E9;
            border-radius: 10px; cursor: pointer; display: flex; align-items: center;
            justify-content: center; font-size: 18px; color: #8a8580;
            transition: border-color 0.15s, color 0.15s; flex-shrink: 0;
        }
        .rv-drawer-close:hover { border-color: #245E55; color: #245E55; }
        .rv-drawer-body { flex: 1; overflow-y: auto; padding: 24px 28px; }
        .rv-drawer-body::-webkit-scrollbar { width: 4px; }
        .rv-drawer-body::-webkit-scrollbar-thumb { background: #DDD8CF; border-radius: 99px; }
        .rv-drawer-footer {
            padding: 16px 28px; border-top: 1px solid #DDD8CF;
            display: flex; gap: 8px; justify-content: flex-end; flex-shrink: 0;
        }

        /* ── Mobile ── */
        .rv-mobile-toggle { display: none; background: none; border: none; font-size: 22px; cursor: pointer; color: #555; padding: 4px; }

        @media (max-width: 768px) {
            .rv-sidebar { transform: translateX(-100%); }
            .rv-sidebar.open { transform: translateX(0); }
            .rv-main { margin-left: 0; }
            .rv-mobile-toggle { display: block; }
            .rv-content { padding: 20px; }
        }
    </style>

    @yield('head')
</head>
<body>

<aside class="rv-sidebar" id="rvSidebar">
    <a class="rv-sidebar-brand" href="{{ route('dashboard') }}">
        
        <!-- New PCC logo beside SVG -->
        <img src="{{ asset('assets/img/icons/pcc.png') }}" 
             alt="PCC Logo" 
             width="44" height="44" 
             style="margin-left:12px; border-radius:4px;">
        <!-- Existing SVG logo, enlarged -->
        <svg width="44" height="44" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 8 C3 8 3 21 3 22 Q9 20 16 22 L16 9 Q10 7 3 8 Z" fill="#2563EB"/>
            <path d="M5 9.5 C5 9.5 5 20 5 21 Q10 19.5 15 21.5 L15 10 Q10 8.5 5 9.5 Z" fill="#3B82F6"/>
            <path d="M29 8 C29 8 29 21 29 22 Q23 20 16 22 L16 9 Q22 7 29 8 Z" fill="#EA6C00"/>
            <path d="M27 9.5 C27 9.5 27 20 27 21 Q22 19.5 17 21.5 L17 10 Q22 8.5 27 9.5 Z" fill="#F97316"/>
            <path d="M3 22 Q10 25 16 24 Q22 25 29 22" stroke="#1E3A8A" stroke-width="1.2" fill="none" stroke-linecap="round"/>
            <line x1="16" y1="9" x2="16" y2="24" stroke="rgba(0,0,0,0.25)" stroke-width="1"/>
            <path d="M9 15 L13.5 20 L23 10" stroke="#22C55E" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>


        <!-- Enlarged brand name -->
        <span class="rv-sidebar-brand-name" style="font-size:24px;">Reviso</span>
    </a>

        @php
            $teacherNavClassId = \App\Models\ClassModel::query()
                ->where('created_by', Auth::id())
                ->value('id');
        @endphp

        <nav class="rv-nav">
            <span class="rv-nav-section">Main</span>

            <a class="rv-nav-item {{ request()->routeIs('teacherDashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="rv-nav-icon"><i class="fas fa-home"></i></span> Dashboard
            </a>

            <a class="rv-nav-item {{ request()->routeIs('manageclass') ? 'active' : '' }}" href="{{ route('manageclass') }}">
                <span class="rv-nav-icon"><i class="fas fa-chalkboard-teacher"></i></span> Class Management
            </a>

            <a class="rv-nav-item {{ request()->routeIs('test-bank.*') ? 'active' : '' }}" href="{{ route('test-bank.index') }}">
                <span class="rv-nav-icon"><i class="fas fa-database"></i></span> Test Bank
            </a>

            <a class="rv-nav-item {{ request()->routeIs('student.performance', 'student.performance.*', 'student.assessment.analysis*') ? 'active' : '' }}"
                    href="{{ $teacherNavClassId ? route('student.performance', ['class' => $teacherNavClassId]) : route('manageclass') }}">
                <span class="rv-nav-icon"><i class="fas fa-chart-bar"></i></span> Student Performance
            </a>

            <a class="rv-nav-item {{ request()->routeIs('student.progress.tracker') ? 'active' : '' }}"
                    href="{{ $teacherNavClassId ? route('student.progress.tracker', ['class' => $teacherNavClassId]) : route('manageclass') }}">
                <span class="rv-nav-icon"><i class="fas fa-tasks"></i></span> Class Progress Tracker
            </a>

            <a class="rv-nav-item {{ request()->routeIs('mock-boards.batch.*', 'mock-boards.index', 'mock-boards.analysis') ? 'active' : '' }}"
   href="{{ route('mock-boards.batch.dashboard') }}">
    <span class="rv-nav-icon"><i class="fas fa-graduation-cap"></i></span> Mock Boards
</a>


         


        </nav>

        <div class="rv-sidebar-footer">
            <a class="rv-user-pill" href="{{ route('profile') }}">
                <img src="{{ asset('assets/img/icons/icon.jpg') }}" class="rv-user-avatar" alt="Profile">
                <div style="overflow:hidden;flex:1;">
                    <div class="rv-user-name">{{ Auth::user()->name ?? Auth::user()->idnumber }}</div>
                    <div class="rv-user-role">{{ ucfirst(Auth::user()->role) }}</div>
                </div>
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="rv-signout-btn">
                    <i class="fas fa-sign-out-alt" style="margin-right:8px;"></i> Sign out
                </button>
            </form>
        </div>
    </aside>

    <div class="rv-main">
        <header class="rv-topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="rv-mobile-toggle" id="rvMobileToggle"><i class="fas fa-bars"></i></button>
                <span class="rv-topbar-title">@yield('page-heading', 'Dashboard')</span>
            </div>
            <div class="rv-topbar-right">@yield('header-actions')</div>
        </header>

        <main class="rv-content">
            @if(session('success'))
                <div class="rv-alert rv-alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rv-alert rv-alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="rv-alert rv-alert-danger">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <div class="rv-overlay" id="rvOverlay"></div>

    @yield('drawers')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts-before-argon')
    @yield('scripts')

    <script>
        // Mobile sidebar
        document.getElementById('rvMobileToggle')?.addEventListener('click', function () {
            document.getElementById('rvSidebar').classList.toggle('open');
            document.getElementById('rvOverlay').classList.toggle('open');
        });

        // Overlay click — close sidebar + all drawers
        document.getElementById('rvOverlay').addEventListener('click', function () {
            document.getElementById('rvSidebar').classList.remove('open');
            document.querySelectorAll('.rv-drawer.open').forEach(d => d.classList.remove('open'));
            this.classList.remove('open');
        });

        // Global drawer helpers
        window.RvDrawer = {
            open: function (id) {
                document.getElementById(id)?.classList.add('open');
                document.getElementById('rvOverlay').classList.add('open');
            },
            close: function (id) {
                document.getElementById(id)?.classList.remove('open');
                if (!document.querySelector('.rv-drawer.open')) {
                    document.getElementById('rvOverlay').classList.remove('open');
                }
            }
        };
    </script>
</body>
</html>
