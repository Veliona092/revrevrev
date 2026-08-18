<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reviso — Account Not Approved</title>
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
            padding: 2rem;
        }

        .card {
            background: #fff;
            border: 1px solid #ebebeb;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }

        .icon-wrap {
            width: 64px; height: 64px; border-radius: 16px;
            background: #fee2e2; color: #991b1b;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; margin: 0 auto 20px;
        }

        h1 {
            font-family: 'DM Sans', sans-serif;
            font-size: 24px; font-weight: 400;
            color: #111; margin: 0 0 8px;
            letter-spacing: -0.02em;
        }

        .sub {
            font-size: 14px; color: #888; line-height: 1.6; margin: 0 0 28px;
        }

        .info-box {
            background: #fafafa;
            border: 1px solid #ebebeb;
            border-radius: 10px;
            padding: 16px 20px;
            text-align: left;
            margin-bottom: 28px;
            font-size: 13px;
            color: #555;
            line-height: 1.6;
        }

        .back-btn {
            height: 38px; padding: 0 20px;
            background: #fff; color: #555; border: 1px solid #e4e4e4;
            border-radius: 8px; font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 500; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            transition: border-color 0.15s, color 0.15s;
        }

        .back-btn:hover { border-color: #111; color: #111; }

        .email-note {
            font-size: 12px; color: #bbb; margin-top: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <i class="fas fa-times-circle"></i>
        </div>

        <h1>Account Not Approved</h1>
        <p class="sub">
            Your account for <strong>{{ session('account_email', 'your email') }}</strong> was reviewed but could not be approved at this time.
        </p>

        <div class="info-box">
            <p>If you believe this is an error or would like more information, please contact your institution's administrator directly.</p>
        </div>

        <a href="{{ route('login') }}" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>
</body>
</html>
