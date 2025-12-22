<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
      integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer" />

    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50">
    {{-- Background tanpa “bayang-bayang aneh” --}}
    <div class="min-h-screen w-full bg-gradient-to-b from-slate-50 via-white to-slate-50">
        <main class="min-h-screen flex items-center justify-center px-4 py-10">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
