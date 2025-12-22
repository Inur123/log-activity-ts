<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Super Admin' }}</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @livewireStyles
    @stack('styles')
</head>

<body class="bg-slate-50 min-h-screen">
    <div class="min-h-screen flex"
         x-data="{ sidebarOpen:false }"
         x-on:keydown.escape.window="sidebarOpen=false">

        {{-- Sidebar --}}
        @include('layouts.super-admin.sidebar')

        {{-- Overlay (mobile only) --}}
        <div class="fixed inset-0 z-30 bg-black/40 lg:hidden"
             x-show="sidebarOpen"
             x-transition.opacity
             x-cloak
             x-on:click="sidebarOpen=false"></div>

        {{-- MAIN AREA --}}
        <div class="flex-1 flex flex-col lg:ml-72 min-w-0">

            {{-- Header --}}
            @include('layouts.super-admin.header')

            {{-- Content --}}
            <main class="flex-1 p-4 sm:p-6 pb-20">
                {{ $slot }}
            </main>

            {{-- Footer --}}
            @include('layouts.super-admin.footer')

        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
