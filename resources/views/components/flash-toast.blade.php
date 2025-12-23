<div
    x-data="{
        show: false,
        message: '',
        type: 'success',
        timeout: null,
        open(t, m) {
            this.type = t || 'success';
            this.message = m || '';
            this.show = true;

            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => this.show = false, 1500);
        }
    }"
    x-init="
        @if(session()->has('toast'))
            open(@js(session('toast')['type'] ?? 'success'), @js(session('toast')['message'] ?? ''));
        @endif
    "
    x-on:flash.window="open($event.detail.type, $event.detail.message)"
    x-cloak
    class="
        fixed z-[9999] pointer-events-none
        top-4 left-0 right-0 px-4
        sm:top-5 sm:left-auto sm:right-5 sm:px-0
    "
>
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-8"
        class="pointer-events-auto w-full max-w-none sm:max-w-sm sm:w-[24rem]"
    >
        <div
            class="rounded-2xl border shadow-lg bg-white overflow-hidden"
            :class="{
                'border-emerald-200': type === 'success',
                'border-amber-200': type === 'warning',
                'border-rose-200': type === 'error',
                'border-slate-200': type === 'info',
            }"
        >
            <div class="flex items-start gap-3 p-4">
                <div
                    class="h-10 w-10 rounded-2xl flex items-center justify-center text-white shrink-0"
                    :class="{
                        'bg-emerald-600': type === 'success',
                        'bg-amber-500': type === 'warning',
                        'bg-rose-600': type === 'error',
                        'bg-slate-700': type === 'info',
                    }"
                >
                    <i class="fa-solid"
                       :class="{
                           'fa-circle-check': type === 'success',
                           'fa-triangle-exclamation': type === 'warning',
                           'fa-circle-xmark': type === 'error',
                           'fa-circle-info': type === 'info',
                       }"></i>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-slate-900 capitalize" x-text="type"></div>
                    <div class="text-sm text-slate-600 break-words" x-text="message"></div>
                </div>

                <button type="button"
                    class="h-8 w-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 shrink-0"
                    @click="show=false">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>
