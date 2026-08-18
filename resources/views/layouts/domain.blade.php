<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Course Module')</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            width: 100%; height: 100%;
            overflow: hidden;
        }

        body {
            display: flex;
            flex-direction: column;
            font-family: 'DM Sans', sans-serif;
            font-size: 17px;
            line-height: 1.55;
            background: #f8f9fa;
        }

        /* ── Top bar ── */
        .top-bar {
            background: #002060;
            color: #fff;
            height: 70px;
            min-height: 70px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .top-bar-left  { display: flex; align-items: center; gap: 12px; }
        .top-bar-right { display: flex; align-items: center; gap: 10px; }

        .top-bar-logo { height: 40px; width: auto; }

        .top-bar-title {
            font-size: 22px;
            font-weight: 600;
            color: #fff;
            margin: 0;
        }

        .top-bar-btn {
            width: 42px; height: 42px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 17px;
            transition: background 0.15s;
        }

        .top-bar-btn:hover { background: rgba(255,255,255,0.22); }

        /* ── Content wrapper ── */
        .content-wrapper {
            flex: 1;
            min-height: 0;
            display: flex;
            overflow: hidden;
        }

        @media (max-width: 992px) {
            .top-bar-title { font-size: 18px; }
        }
    </style>

    @yield('head')
</head>
<body>

    <nav class="top-bar">
        <div class="top-bar-left">
            <img src="{{ asset('assets/img/icons/pcc.png') }}" alt="PCC" class="top-bar-logo">
            <svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 8 C3 8 3 21 3 22 Q9 20 16 22 L16 9 Q10 7 3 8 Z" fill="#2563EB"/>
                <path d="M5 9.5 C5 9.5 5 20 5 21 Q10 19.5 15 21.5 L15 10 Q10 8.5 5 9.5 Z" fill="#3B82F6"/>
                <path d="M29 8 C29 8 29 21 29 22 Q23 20 16 22 L16 9 Q22 7 29 8 Z" fill="#EA6C00"/>
                <path d="M27 9.5 C27 9.5 27 20 27 21 Q22 19.5 17 21.5 L17 10 Q22 8.5 27 9.5 Z" fill="#F97316"/>
                <path d="M3 22 Q10 25 16 24 Q22 25 29 22" stroke="#1E3A8A" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                <path d="M9 15 L13.5 20 L23 10" stroke="#22C55E" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h5 class="top-bar-title">@yield('page-title', $class->name ?? 'Course')</h5>
        </div>
        <div class="top-bar-right">
            <button class="top-bar-btn"><i class="fas fa-bookmark"></i></button>
            <a href="{{ route('dashboard') }}" class="top-bar-btn" title="Back to dashboard"><i class="fas fa-home"></i></a>
        </div>
    </nav>

    <div class="content-wrapper">
        @yield('content')
    </div>

    @yield('scripts')

</body>
</html>
