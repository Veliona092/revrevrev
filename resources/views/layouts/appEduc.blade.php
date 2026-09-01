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
    --sidebar-w: 286px;
    --sidebar-bg: #3A4180;
    --sidebar-border: rgba(255,255,255,0.10);
    --nav-text: rgba(255,255,255,0.60);
    --nav-text-hover: rgba(255,255,255,0.95);
    --nav-active-bg: rgba(255,255,255,0.12);
    --nav-active-text: #fff;
    --nav-active-bar: #9ED6DF;
    --content-bg: #F0EEF8;
    --font: 'DM Sans', sans-serif;
    --font-serif: 'DM Sans', sans-serif;/
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
        .rv-nav-item.active .rv-nav-icon,
        .rv-nav-item:hover .rv-nav-icon { opacity: 1; }

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
            color: rgba(255,255,255,0.35); text-align: left;
            transition: color 0.15s, background 0.15s;
            margin-top: 6px;
        }

        .rv-signout-btn:hover { color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.05); }

        /* ── Main ── */
        .rv-main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ── Topbar ── */
        .rv-topbar {
            background: #fff;
            border-bottom: 1px solid #e8e8e6;
            padding: 0 34px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .rv-topbar-title { font-family: var(--font-serif); font-size: 30px; color: #111; font-weight: 500; letter-spacing: -0.02em; }
        .rv-topbar-right { display: flex; align-items: center; gap: 10px; }

        /* ── Content ── */
        .rv-content { flex: 1; padding: 36px; }

        /* ── Shared buttons ── */
        .rv-btn {
            height: 46px; padding: 0 20px; border-radius: 10px;
            font-family: var(--font); font-size: 16px; font-weight: 500;
            cursor: pointer; transition: background 0.15s, transform 0.1s, border-color 0.15s;
            border: 1px solid transparent;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .rv-btn:active { transform: scale(0.98); }
        .rv-btn-primary { background: #0f0f0f; color: #fff; border-color: #0f0f0f; }
        .rv-btn-primary:hover { background: #333; }
        .rv-btn-secondary { background: #fff; color: #555; border-color: #e4e4e4; }
        .rv-btn-secondary:hover { border-color: #bbb; color: #111; }
        .rv-btn-danger { background: #fff; color: #e24b4a; border-color: #f7c1c1; }
        .rv-btn-danger:hover { background: #fcebeb; }
        .rv-btn-success { background: #1d9e75; color: #fff; border-color: #1d9e75; }
        .rv-btn-success:hover { background: #0f6e56; }

        .rv-label { display: block; font-size: 14px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase; color: #aaa; margin-bottom: 8px; }
        .rv-input, .rv-select, .rv-textarea {
            width: 100%; padding: 12px 14px; border: 1px solid #e4e4e4; border-radius: 10px;
            font-family: var(--font); font-size: 17px; color: #111; background: #fff; outline: none;
            transition: border-color 0.15s, box-shadow 0.15s; appearance: none;
        }
        .rv-textarea { resize: vertical; min-height: 96px; line-height: 1.55; }
        .rv-input:focus, .rv-select:focus, .rv-textarea:focus { border-color: #111; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        .rv-input::placeholder, .rv-textarea::placeholder { color: #ccc; }
        .rv-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23aaa' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
        }
        .rv-form-group { margin-bottom: 22px; }

        .rv-alert { padding: 13px 16px; border-radius: 10px; font-size: 16px; margin-bottom: 18px; }
        .rv-alert-success { background: #e1f5ee; color: #0f6e56; }
        .rv-alert-danger  { background: #fcebeb; color: #a32d2d; }

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
            background: #fff; z-index: 200;
            display: flex; flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.28s cubic-bezier(0.4,0,0.2,1);
            box-shadow: -8px 0 40px rgba(0,0,0,0.12);
        }
        .rv-drawer.open { transform: translateX(0); }

        .rv-drawer-head {
            padding: 24px 28px 18px; border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: flex-start; justify-content: space-between; flex-shrink: 0;
        }
        .rv-drawer-title { font-family: var(--font-serif); font-size: 24px; color: #111; font-weight: 500; }
        .rv-drawer-subtitle { font-size: 15px; color: #aaa; margin-top: 4px; }
        .rv-drawer-close {
            width: 38px; height: 38px; border: 1px solid #e4e4e4; background: #fff;
            border-radius: 10px; cursor: pointer; display: flex; align-items: center;
            justify-content: center; font-size: 18px; color: #888;
            transition: border-color 0.15s, color 0.15s; flex-shrink: 0;
        }
        .rv-drawer-close:hover { border-color: #111; color: #111; }
        .rv-drawer-body { flex: 1; overflow-y: auto; padding: 24px 28px; }
        .rv-drawer-body::-webkit-scrollbar { width: 4px; }
        .rv-drawer-body::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 99px; }
        .rv-drawer-footer {
            padding: 16px 28px; border-top: 1px solid #f0f0f0;
            display: flex; gap: 8px; justify-content: flex-end; flex-shrink: 0;
        }

        /* ── Mobile ── */
        .rv-mobile-toggle { display: none; background: none; border: none; font-size: 22px; cursor: pointer; color: #555; padding: 4px; }

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
          <img src="{{ asset('assets/img/icons/pcc.png') }}" 
             alt="PCC Logo" 
             width="44" height="44" 
             style="margin-left:8px; border-radius:4px;">
        <!-- Existing SVG logo -->
        <svg width="44" height="44" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            
            <path d="M3 8 C3 8 3 21 3 22 Q9 20 16 22 L16 9 Q10 7 3 8 Z" fill="#2563EB"/>
            <path d="M5 9.5 C5 9.5 5 20 5 21 Q10 19.5 15 21.5 L15 10 Q10 8.5 5 9.5 Z" fill="#3B82F6"/>
            <path d="M29 8 C29 8 29 21 29 22 Q23 20 16 22 L16 9 Q22 7 29 8 Z" fill="#EA6C00"/>
            <path d="M27 9.5 C27 9.5 27 20 27 21 Q22 19.5 17 21.5 L17 10 Q22 8.5 27 9.5 Z" fill="#F97316"/>
            <path d="M3 22 Q10 25 16 24 Q22 25 29 22" stroke="#1E3A8A" stroke-width="1.2" fill="none" stroke-linecap="round"/>
            <line x1="16" y1="9" x2="16" y2="24" stroke="rgba(0,0,0,0.25)" stroke-width="1"/>
            <path d="M9 15 L13.5 20 L23 10" stroke="#22C55E" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
  

        <span class="rv-sidebar-brand-name text">Reviso</span>
        <!-- New PCC logo beside the SVG -->
    
    </a>



        <nav class="rv-nav">
            <span class="rv-nav-section">Main</span>

            <a class="rv-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="rv-nav-icon"><i class="fas fa-home"></i></span> Dashboard
            </a>

            <a class="rv-nav-item {{ request()->routeIs('student.classes') ? 'active' : '' }}" href="{{ route('student.classes') }}">
                <span class="rv-nav-icon"><i class="fas fa-book-open"></i></span> Lectures
            </a>

            <a class="rv-nav-item {{ request()->routeIs('progress') ? 'active' : '' }}" href="{{ route('progress') }}">
                <span class="rv-nav-icon"><i class="fas fa-chart-bar"></i></span> Progress Tracker
            </a>



       

        <a class="rv-nav-item {{ request()->is('student/mock-boards*') ? 'active' : '' }}" 
   href="{{ route('student.mock-boards.index') }}">
    <span class="rv-nav-icon"><i class="fas fa-clipboard-check"></i></span> Mock Boards
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