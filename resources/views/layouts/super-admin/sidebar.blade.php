<aside
    class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-slate-200 flex flex-col
           transition-transform duration-300
           -translate-x-full lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    {{-- Header --}}
    <div class="h-16 flex items-center justify-between px-4">
        <div class="flex items-center gap-2">
            <div class="h-8 w-8 bg-slate-900 text-white rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-gauge"></i>
            </div>
            <div>
                <div class="font-bold">Super Admin</div>
                <div class="text-xs text-slate-500">Admin Panel</div>
            </div>
        </div>

        <button
            type="button"
            class="lg:hidden p-2 rounded-lg hover:bg-slate-100"
            x-on:click="sidebarOpen=false"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="px-3 py-4 space-y-1">
        <a
            href="{{ route('super_admin.dashboard') }}"
            wire:navigate
            class="flex items-center gap-3 px-3 py-2 rounded-xl
            {{ request()->routeIs('super_admin.dashboard')
                ? 'bg-slate-900 text-white'
                : 'hover:bg-slate-100 text-slate-700' }}"
            x-on:click="sidebarOpen=false"
        >
            <i class="fa-solid fa-house"></i>
            Dashboard
        </a>

        <a
            href="{{ route('super_admin.logs') }}"
            wire:navigate
            class="flex items-center gap-3 px-3 py-2 rounded-xl
            {{ request()->routeIs('super_admin.logs')
                ? 'bg-slate-900 text-white'
                : 'hover:bg-slate-100 text-slate-700' }}"
            x-on:click="sidebarOpen=false"
        >
            <i class="fa-solid fa-database"></i>
            Log Viewer
        </a>
        <a
    href="{{ route('super_admin.applications') }}"
    wire:navigate
    class="flex items-center gap-3 px-3 py-2 rounded-xl
    {{ request()->routeIs('super_admin.applications')
        ? 'bg-slate-900 text-white'
        : 'hover:bg-slate-100 text-slate-700' }}"
    x-on:click="sidebarOpen=false"
>
    <i class="fa-solid fa-cubes"></i>
    Applications
</a>

    </nav>

    {{-- LOGOUT (LIVEWIRE) --}}
    <div class="mt-auto p-4">
        @livewire('auth.logout')
    </div>
</aside>
