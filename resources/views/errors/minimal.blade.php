@php
    $code = $exception?->getStatusCode() ?? 500;
@endphp

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Error {{ $code }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body
    style="
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:system-ui, sans-serif;
    background:#0f172a;
    color:#e5e7eb;
">
    <div style="text-align:center">
        <div style="font-size:14px; opacity:.7">ERROR</div>
        <div style="font-size:48px; font-weight:700; margin:8px 0">
            {{ $code }}
        </div>
        <div style="font-size:14px; opacity:. a8">
            {{ $exception?->getMessage() ?: 'Terjadi kesalahan.' }}
        </div>
    </div>
</body>

</html>
