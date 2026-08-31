<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reviso — Pending Approval</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/js/plugins/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f5f4;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.4rem;
        }

        .card {
            background: #fff;
            border: 1px solid #ebebeb;
            border-radius: 18px;
            padding: 3.4rem 2.9rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }

        .icon-wrap {
            width: 72px; height: 72px; border-radius: 18px;
            background: #faeeda; color: #854f0b;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 24px;
        }

        h1 {
            font-family: 'DM Sans', serif;
            font-size: 29px; font-weight: 500;
            color: #111; margin: 0 0 10px;
            letter-spacing: -0.02em;
        }

        .sub {
            font-size: 16px; color: #888; line-height: 1.65; margin: 0 0 32px;
        }

        .steps {
            background: #fafafa;
            border: 1px solid #ebebeb;
            border-radius: 12px;
            padding: 18px 22px;
            text-align: left;
            margin-bottom: 32px;
        }

        .step {
            display: flex; align-items: center; gap: 12px;
            font-size: 15px; color: #555; padding: 9px 0;
            border-bottom: 1px solid #f3f3f3;
        }

        .step:last-child { border-bottom: none; }

        .step-num {
            width: 26px; height: 26px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 500; flex-shrink: 0;
        }

        .step-num.done    { background: #e1f5ee; color: #0f6e56; }
        .step-num.current { background: #faeeda; color: #854f0b; }
        .step-num.pending { background: #f3f3f3; color: #aaa; }

        .step-label { flex: 1; }
        .step-label.done    { color: #111; }
        .step-label.current { color: #854f0b; font-weight: 500; }
        .step-label.pending { color: #bbb; }

        .logout-btn {
            height: 44px; padding: 0 24px;
            background: #fff; color: #555; border: 1px solid #e4e4e4;
            border-radius: 9px; font-family: 'DM Sans', sans-serif;
            font-size: 15px; font-weight: 500; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            transition: border-color 0.15s, color 0.15s;
        }

        .logout-btn:hover { border-color: #111; color: #111; }

        .email-note {
            font-size: 14px; color: #bbb; margin-top: 18px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <i class="fas fa-hourglass-half"></i>
        </div>

        <h1>Awaiting Approval</h1>
        <p class="sub">
            Your email has been verified. Your account is now waiting for an administrator to review and approve it.
            You'll receive an email as soon as it's approved.
        </p>

        <div class="steps">
            <div class="step">
                <div class="step-num done"><i class="fas fa-check" style="font-size:10px;"></i></div>
                <span class="step-label done">Account created</span>
            </div>
            <div class="step">
                <div class="step-num done"><i class="fas fa-check" style="font-size:10px;"></i></div>
                <span class="step-label done">Email verified</span>
            </div>
            <div class="step">
                <div class="step-num current"><i class="fas fa-clock" style="font-size:11px;"></i></div>
                <span class="step-label current">Waiting for admin approval</span>
            </div>
            <div class="step">
                <div class="step-num pending">4</div>
                <span class="step-label pending">Access granted</span>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Sign out
            </button>
        </form>

        <p class="email-note">
            We'll send an email to <strong>{{ session('account_email', Auth::user()->email ?? 'your email') }}</strong> when your account is approved.
        </p>
    </div>
</body>
</html>