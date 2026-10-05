<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ config('site.short_name') }} — back soon</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; background: #fffaf0; color: #1a1a1a; padding: 24px; }
        main { max-width: 520px; text-align: center; }
        h1 { font-size: 1.75rem; margin: 0 0 12px; }
        p { line-height: 1.6; color: #444; white-space: pre-line; }
        a { color: inherit; font-weight: 600; }
    </style>
</head>
<body>
    <main>
        <h1>{{ config('site.name') }}</h1>
        <p>{{ $message }}</p>
        <p><a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a> · {{ config('site.phone') }}</p>
    </main>
</body>
</html>
