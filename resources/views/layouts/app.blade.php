<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title','Dashboard')</title>

    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('styles')
</head>

<body class="bg-slate-50 min-h-screen">

<div class="min-h-screen flex">

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- Overlay (mobile only) --}}
    <div id="sidebarOverlay"
         class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden"></div>

    {{-- MAIN AREA --}}
    <div class="flex-1 flex flex-col lg:ml-72 min-w-0">

        {{-- Header --}}
        @include('layouts.header')

        {{-- Content --}}
        <main class="flex-1 p-4 sm:p-6 pb-20">
    @yield('content')
</main>

        {{-- Footer --}}
        @include('layouts.footer')

    </div>
</div>

{{-- Sidebar Toggle --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('sidebarOpenBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    openBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);
});
</script>

@stack('scripts')
</body>
</html>
