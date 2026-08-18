{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Reviso')</title>

    <link href="{{ asset('assets/img/brand/favicon.png') }}" rel="icon" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet" />

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
    --sidebar-w: 252px;
    --sidebar-bg: #20180f;
    --sidebar-bg-2: #2c2115;
    --sidebar-border: rgba(255,255,255,0.09);
    --nav-text: rgba(255,255,255,0.64);
    --nav-text-hover: rgba(255,255,255,0.97);
    --nav-active-bg: rgba(255,255,255,0.12);
    --nav-active-text: #fff;
    --nav-active-bar: #e2a53d;
    --content-bg: #f5f0e8;
    --content-bg-2: #efe6d7;
    --surface: #fdfaf4;
    --surface-border: #e7d9c2;
    --ink: #2f271f;
    --muted: #8d7b67;
    --accent: #c8871b;
    --accent-2: #f3c86d;
    --font: 'DM Sans', sans-serif;
    --font-serif: 'DM Sans', serif;
}

        body {
            font-family: var(--font);
            background:
                radial-gradient(circle at 18% -8%, rgba(243, 200, 109, 0.22), transparent 42%),
                radial-gradient(circle at 85% 10%, rgba(174, 126, 47, 0.1), transparent 38%),
                linear-gradient(180deg, var(--content-bg), var(--content-bg-2));
            color: var(--ink);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .rv-sidebar {
            width: var(--sidebar-w);
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, var(--sidebar-bg-2) 100%);
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
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .rv-sidebar-brand-name {
            font-family: var(--font-serif);
            font-size: 24px;
            color: #fff;
            letter-spacing: 0.01em;
        }

        .rv-nav { flex: 1; padding: 12px 10px; overflow-y: auto; }
        .rv-nav::-webkit-scrollbar { width: 0; }

        .rv-nav-section {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.25);
            padding: 14px 10px 6px;
        }

        .rv-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 10px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--nav-text);
            font-size: 14px;
            transition: background 0.18s, color 0.18s, transform 0.18s;
            position: relative;
            margin-bottom: 2px;
        }

        .rv-nav-item:hover {
            background: rgba(255,255,255,0.07);
            color: var(--nav-text-hover);
            transform: translateX(2px);
        }

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
            width: 3px; height: 18px;
            background: var(--nav-active-bar);
            border-radius: 0 3px 3px 0;
        }

        .rv-nav-icon { width: 18px; text-align: center; font-size: 13px; opacity: 0.7; flex-shrink: 0; }
        .rv-nav-item.active .rv-nav-icon,
        .rv-nav-item:hover .rv-nav-icon { opacity: 1; }

        .rv-sidebar-footer { padding: 14px 10px; border-top: 1px solid var(--sidebar-border); }

        .rv-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
        }

        .rv-user-pill:hover { background: rgba(255,255,255,0.06); }

        .rv-user-avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.15);
            object-fit: cover;
            flex-shrink: 0;
        }

        .rv-user-name { font-size: 13px; color: rgba(255,255,255,0.8); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rv-user-role { font-size: 11px; color: rgba(255,255,255,0.35); }

        .rv-signout-btn {
            width: 100%; background: none; border: none; cursor: pointer;
            padding: 8px 10px; border-radius: 8px;
            font-family: var(--font); font-size: 13px;
            color: rgba(255,255,255,0.35); text-align: left;
            transition: color 0.15s, background 0.15s;
            margin-top: 4px;
        }

        .rv-signout-btn:hover { color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.05); }

        /* ── Main ── */
        .rv-main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ── Topbar ── */
        .rv-topbar {
            background: rgba(253, 248, 240, 0.86);
            border-bottom: 1px solid #e9dbc7;
            backdrop-filter: blur(10px);
            padding: 0 28px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .rv-topbar-title { font-family: var(--font-serif); font-size: 30px; color: #2b231b; font-weight: 400; letter-spacing: 0.01em; }
        .rv-topbar-right { display: flex; align-items: center; gap: 10px; }

        /* ── Content ── */
        .rv-content { flex: 1; padding: 28px; zoom: 1.12; }

        .rv-content canvas { zoom: 0.8; }

        /* ── Shared buttons ── */
        .rv-btn {
            height: 38px; padding: 0 18px; border-radius: 8px;
            font-family: var(--font); font-size: 13px; font-weight: 500;
            cursor: pointer; transition: background 0.15s, transform 0.1s, border-color 0.15s;
            border: 1px solid transparent;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .rv-btn:active { transform: scale(0.98); }
        .rv-btn-primary { background: linear-gradient(135deg, #c8871b, #b47210); color: #fff; border-color: #b47210; }
        .rv-btn-primary:hover { background: linear-gradient(135deg, #b97a14, #9c650f); border-color: #9c650f; }
        .rv-btn-secondary { background: #F7F3EC; color: #5a5040; border-color: #E8E0D0; }
        .rv-btn-secondary:hover { border-color: #C17F24; color: #2D2D2B; }
        .rv-btn-danger { background: #F7F3EC; color: #C63F3E; border-color: #e8b8b7; }
        .rv-btn-danger:hover { background: #f5e3e3; }
        .rv-btn-success { background: #245E55; color: #fff; border-color: #245E55; }
        .rv-btn-success:hover { background: #1a4840; border-color: #1a4840; }

        .rv-label { display: block; font-size: 11px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase; color: #9a8f80; margin-bottom: 6px; }
        .rv-input, .rv-select, .rv-textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #e4d4bc; border-radius: 10px;
            font-family: var(--font); font-size: 14px; color: #2D2D2B; background: #fdfaf4; outline: none;
            transition: border-color 0.15s, box-shadow 0.15s; appearance: none;
        }
        .rv-textarea { resize: vertical; min-height: 80px; line-height: 1.5; }
        .rv-input:focus, .rv-select:focus, .rv-textarea:focus { border-color: #c8871b; box-shadow: 0 0 0 3px rgba(200, 135, 27, 0.14); }
        .rv-input::placeholder, .rv-textarea::placeholder { color: #C8BBA8; }
        .rv-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23aaa' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
        }
        .rv-form-group { margin-bottom: 18px; }

        .rv-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 14px; }
        .rv-alert-success { background: #d4e8e5; color: #1a4840; }
        .rv-alert-danger  { background: #f5e3e3; color: #9e2f2e; }

        /* ── Drawer ── */
        .rv-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.35); z-index: 199;
            backdrop-filter: blur(2px);
        }
        .rv-overlay.open { display: block; }

        .rv-drawer {
            position: fixed; top: 0; right: 0; bottom: 0;
            width: 500px; max-width: 95vw;
            background: #FBF8F3; z-index: 200;
            display: flex; flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.28s cubic-bezier(0.4,0,0.2,1);
            box-shadow: -8px 0 40px rgba(0,0,0,0.12);
        }
        .rv-drawer.open { transform: translateX(0); }

        .rv-drawer-head {
            padding: 20px 24px 16px; border-bottom: 1px solid #E8E0D0;
            display: flex; align-items: flex-start; justify-content: space-between; flex-shrink: 0;
        }
        .rv-drawer-title { font-family: var(--font-serif); font-size: 18px; color: #2D2D2B; font-weight: 400; }
        .rv-drawer-subtitle { font-size: 12px; color: #9a8f80; margin-top: 3px; }
        .rv-drawer-close {
            width: 32px; height: 32px; border: 1px solid #E8E0D0; background: #F7F3EC;
            border-radius: 8px; cursor: pointer; display: flex; align-items: center;
            justify-content: center; font-size: 16px; color: #9a8f80;
            transition: border-color 0.15s, color 0.15s; flex-shrink: 0;
        }
        .rv-drawer-close:hover { border-color: #C17F24; color: #C17F24; }
        .rv-drawer-body { flex: 1; overflow-y: auto; padding: 20px 24px; }
        .rv-drawer-body::-webkit-scrollbar { width: 4px; }
        .rv-drawer-body::-webkit-scrollbar-thumb { background: #E8E0D0; border-radius: 99px; }
        .rv-drawer-footer {
            padding: 14px 24px; border-top: 1px solid #E8E0D0;
            display: flex; gap: 8px; justify-content: flex-end; flex-shrink: 0;
        }

        /* ── Mobile ── */
        .rv-mobile-toggle { display: none; background: none; border: none; font-size: 20px; cursor: pointer; color: #555; padding: 4px; }

        @media (max-width: 768px) {
            .rv-sidebar { transform: translateX(-100%); }
            .rv-sidebar.open { transform: translateX(0); }
            .rv-main { margin-left: 0; }
            .rv-mobile-toggle { display: block; }
            .rv-content { padding: 16px; }
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

        <nav class="rv-nav">
            <span class="rv-nav-section">Main</span>

            <a class="rv-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="rv-nav-icon"><i class="fas fa-home"></i></span> Dashboard
            </a>

            <a class="rv-nav-item {{ request()->routeIs('student.classes') ? 'active' : '' }}" href="{{ route('student.classes') }}">
                <span class="rv-nav-icon"><i class="fas fa-book-open"></i></span> Lectures
            </a>

            <a class="rv-nav-item {{ request()->routeIs('assessment') ? 'active' : '' }}" href="{{ route('assessment') }}">
                <span class="rv-nav-icon"><i class="fas fa-tasks"></i></span> Assessment
            </a>

            <a class="rv-nav-item {{ request()->routeIs('progress') ? 'active' : '' }}" href="{{ route('progress') }}">
                <span class="rv-nav-icon"><i class="fas fa-chart-bar"></i></span> Progress Tracker
            </a>

            <span class="rv-nav-section">Communication</span>

            <a class="rv-nav-item {{ request()->routeIs('chat.index') ? 'active' : '' }}" href="{{ route('chat.index') }}">
                <span class="rv-nav-icon"><i class="fas fa-comment-dots"></i></span> Messages
            </a>

            <a class="rv-nav-item {{ request()->routeIs('announcements.index') ? 'active' : '' }}" href="{{ route('announcements.index') }}">
                <span class="rv-nav-icon"><i class="fas fa-bullhorn"></i></span>
                Announcements
                @if(($announcementUnreadCount ?? 0) > 0)
                    <span style="margin-left:auto;background:rgba(255,255,255,0.22);padding:1px 7px;border-radius:99px;font-size:11px;color:#fff;">{{ $announcementUnreadCount }}</span>
                @endif
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

    <script src="{{ asset('assets/js/plugins/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts-before-argon')
    @yield('scripts')

    <script>
        document.getElementById('rvMobileToggle')?.addEventListener('click', function () {
            document.getElementById('rvSidebar').classList.toggle('open');
            document.getElementById('rvOverlay').classList.toggle('open');
        });

        document.getElementById('rvOverlay').addEventListener('click', function () {
            document.getElementById('rvSidebar').classList.remove('open');
            document.querySelectorAll('.rv-drawer.open').forEach(d => d.classList.remove('open'));
            this.classList.remove('open');
        });

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