@php
    $code = 404; // hanya tampilan, bukan status HTTP
@endphp

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error {{ $code }}</title>
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
    "
>
    <div style="text-align:center; max-width:480px">
        <div style="font-size:14px; opacity:.7">ERROR</div>

        <div style="font-size:56px; font-weight:700; margin:8px 0">
            {{ $code }}
        </div>
    </div>
</body>
</html>
