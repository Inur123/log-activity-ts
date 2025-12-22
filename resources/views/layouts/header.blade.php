<header class="sticky top-0 z-20 bg-white border-b border-slate-200">
    <div class="h-16 flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <button id="sidebarOpenBtn"
                class="lg:hidden p-2 rounded-lg  text-black">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div>
                <div class="text-xs text-slate-500">@yield('breadcrumb','Home')</div>
                <div class="font-bold">@yield('page_title','Dashboard')</div>
            </div>
        </div>
    </div>
</header>
