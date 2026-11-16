@php($ar = app()->getLocale() === 'ar')
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $ar ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('code') · SALE7LI</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            font-family: "DM Sans", "Noto Kufi Arabic", ui-sans-serif, system-ui, sans-serif;
            background: #f7f7f4; color: #0d1115; }
        .box { max-width: 460px; text-align: center; }
        .code { font-size: 64px; font-weight: 800; letter-spacing: -.04em; }
        .title { margin-top: 8px; font-size: 20px; font-weight: 700; }
        .msg { margin-top: 12px; color: #64748b; line-height: 1.6; font-size: 14px; }
        .home { display: inline-block; margin-top: 24px; padding: 12px 20px; border-radius: 12px;
            background: #0d1115; color: #fff; text-decoration: none; font-weight: 700; font-size: 14px; }
        .brand { margin-bottom: 20px; font-weight: 800; font-size: 18px; }
        .brand span { color: #f4c430; }
    </style>
</head>
<body>
    <div class="box">
        <div class="brand">SALE<span>7</span>LI</div>
        <div class="code">@yield('code')</div>
        <div class="title">@yield('title')</div>
        <p class="msg">@yield('message')</p>
        <a class="home" href="{{ url('/') }}">{{ $ar ? 'العودة إلى الصفحة الرئيسية' : 'Back to home' }}</a>
    </div>
</body>
</html>
