<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} · {{ $appName }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box;
               font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
               background: #eef4f1; color: #13251f; }
        .card { width: 100%; max-width: 440px; background: #fff; border-radius: 20px; padding: 32px 28px;
                box-shadow: 0 12px 32px rgba(6, 35, 29, .12); text-align: center; }
        .icon { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 50%; display: grid; place-items: center;
                background: {{ $tone === 'wait' ? '#fdf3d7' : '#fde8e8' }}; color: {{ $tone === 'wait' ? '#8a6d1f' : '#b42318' }}; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { margin: 0 0 24px; line-height: 1.55; color: #4f6862; }
        a.button { display: inline-block; padding: 12px 22px; border-radius: 12px; background: #076653; color: #fff;
                   font-weight: 600; text-decoration: none; }
        @media (prefers-color-scheme: dark) {
            body { background: #0b1512; color: #e2f0e6; }
            .card { background: #13221d; box-shadow: none; }
            p { color: #a8c2b8; }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon" aria-hidden="true">
            @if ($tone === 'wait')
                <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
            @else
                <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            @endif
        </div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="button" href="{{ $loginUrl }}">Go to sign-in</a>
    </main>
</body>
</html>
