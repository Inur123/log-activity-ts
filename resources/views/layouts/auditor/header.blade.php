<header class="sticky top-0 z-20 bg-white border-b border-slate-200">
    <div class="h-16 flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <button type="button"
                    class="lg:hidden p-2 rounded-lg text-black hover:bg-slate-100"
                    x-on:click="sidebarOpen=true">
                <i class="fa-solid fa-bars"></i>
            </button>

             <div>
                <div class="text-xs text-slate-500">
                    Home
                </div>
                <div class="font-bold">
                    Dashboard
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden sm:block text-right">
                <div class="text-sm font-semibold text-slate-900">{{ auth()->user()->name ?? '-' }}</div>
                <div class="text-xs text-slate-500">Auditor</div>
            </div>
        </div>
    </div>
</header>
