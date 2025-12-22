{{-- resources/views/livewire/super-admin/application/detail.blade.php --}}
<div class="space-y-6">

    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-xs text-slate-500">Super Admin • Applications</div>
            <h1 class="text-2xl font-bold text-slate-900">Application Detail</h1>
            <p class="text-sm text-slate-600">Detail informasi aplikasi.</p>
        </div>

        <button type="button" wire:click="back"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left"></i>
            Back
        </button>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500 mb-1">UUID</div>
                <div class="font-mono text-xs text-slate-900 break-all">{{ $app->id }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500 mb-1">Name</div>
                <div class="text-sm font-semibold text-slate-900">{{ $app->name }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500 mb-1">Slug</div>
                <div class="text-sm text-slate-900 break-all">{{ $app->slug }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500 mb-1">Domain</div>
                <div class="text-sm text-slate-900 break-all">{{ $app->domain ?? '-' }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500 mb-1">Stack</div>
                <div class="text-sm text-slate-900">{{ $app->stack }}</div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500 mb-1">Status</div>
                @if ($app->is_active)
                    <span
                        class="inline-flex px-2 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs">
                        Active
                    </span>
                @else
                    <span
                        class="inline-flex px-2 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                        Inactive
                    </span>
                @endif
            </div>

            {{-- API KEY (masked + eye) --}}
            {{-- API KEY (compact + eye + copy) --}}
            <div class="sm:col-span-2 p-3 rounded-lg bg-slate-50 border border-slate-200" x-data="{
                show: false,
                copied: false,
                copy(text) {
                    navigator.clipboard.writeText(text);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 1500);
                }
            }">
                <div class="text-xs text-slate-500 mb-1">API Key</div>

                <div class="flex items-center gap-2">
                    {{-- KEY --}}
                    <div class="flex-1 font-mono text-xs text-slate-900 truncate">
                        <span x-show="!show" class="tracking-[0.22em] select-none">
                            ••••••••••••••••••••••••••••••••
                        </span>
                        <span x-show="show" x-cloak>{{ $app->api_key ?? $displayApiKey }}</span>
                    </div>

                    {{-- COPY --}}
                    <button type="button"
                        class="h-9 w-9 inline-flex items-center justify-center rounded-lg
                   border border-slate-200 bg-white hover:bg-slate-100 text-slate-600"
                        x-on:click="copy('{{ $app->api_key ?? $displayApiKey }}')" title="Copy API Key">
                        <i class="fa-regular fa-copy"></i>
                    </button>

                    {{-- EYE --}}
                    <button type="button"
                        class="h-9 w-9 inline-flex items-center justify-center rounded-lg
                   border border-slate-200 bg-white hover:bg-slate-100 text-slate-600"
                        x-on:click="show = !show" title="Show / Hide">
                        <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>

                {{-- HINT --}}
                <div class="mt-1 text-[11px] text-slate-500">
                    Token disembunyikan demi keamanan.
                    <span x-show="copied" class="text-emerald-600 font-medium ml-1" x-cloak>
                        Copied!
                    </span>
                </div>
            </div>


        </div>
    </div>

</div>
