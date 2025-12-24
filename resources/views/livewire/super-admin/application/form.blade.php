{{-- resources/views/livewire/super-admin/application/form.blade.php --}}
<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <div class="text-xs text-slate-500">Super Admin • Applications</div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $selected ? 'Edit Application' : 'Create Application' }}
            </h1>
            <p class="text-sm text-slate-600">
                {{ $selected ? 'Perbarui data aplikasi.' : 'Buat aplikasi baru. API Key akan dibuat otomatis.' }}
            </p>
        </div>
    </div>

    {{-- INFO CARD (EDIT ONLY) --}}
    @if ($selected)
        <div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="text-sm font-semibold text-slate-900">Info</div>

                <button type="button" wire:click="regenerateApiKeyPreview"
                    class="h-9 inline-flex items-center gap-2 px-3 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-sm cursor-pointer">
                    <i class="fa-solid fa-rotate"></i>
                    Regenerate
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- UUID --}}
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                    <div class="text-[11px] text-slate-500 leading-tight">UUID</div>
                    <div class="font-mono text-xs text-slate-900 break-all mt-1">
                        {{ $selected->id }}
                    </div>
                </div>

                {{-- API KEY --}}
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2" x-data="{
                    show: false,
                    copied: false,
                    copy(text) {
                        navigator.clipboard.writeText(text);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 1200);
                    }
                }">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-[11px] text-slate-500 leading-tight">API Key</div>

                        @if ($isPreviewKey ?? false)
                            <span
                                class="inline-flex px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-amber-700 text-[10px]">
                                Preview
                            </span>
                        @endif
                    </div>

                    <div class="mt-1 flex items-center gap-2">
                        <div class="flex-1">
                            <input :type="show ? 'text' : 'password'" readonly value="{{ $displayApiKey }}"
                                class="w-full bg-transparent border-0 p-0
               font-mono text-xs text-slate-900
               focus:outline-none focus:ring-0
               select-all">
                        </div>


                        <button type="button"
                            class="h-8 w-8 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 cursor-pointer"
                            x-on:click="copy('{{ $displayApiKey }}')" title="Copy">
                            <i class="fa-regular fa-copy text-xs"></i>
                        </button>

                        <button type="button"
                            class="h-8 w-8 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 cursor-pointer"
                            x-on:click="show=!show" title="Show/Hide">
                            <i class="fa-solid text-xs" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>

                    <div class="mt-1 text-[11px] text-slate-500">
                        Token disembunyikan demi keamanan.
                        <span x-show="copied" x-cloak class="text-emerald-600 font-medium ml-1">Copied!</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- FORM WRAPPER: Enter otomatis + auto focus --}}
    <form wire:submit.prevent="save" x-data x-init="$nextTick(() => $refs.first?.focus())">
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- NAME --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Name</label>
                    <input x-ref="first" autofocus type="text" wire:model.live="name" placeholder="Nama aplikasi"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-gray-500 focus:ring-0 focus:outline-none">
                    @error('name')
                        <div class="text-xs text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- STACK --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Stack</label>
                    <select wire:model.live="form_stack"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white focus:border-gray-500 focus:ring-0 focus:outline-none">
                        <option value="laravel">Laravel</option>
                        <option value="codeigniter">CodeIgniter</option>
                        <option value="django">Django</option>
                        <option value="other">Other</option>
                    </select>
                    @error('form_stack')
                        <div class="text-xs text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- DOMAIN --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Domain <span class="font-normal text-slate-400">(optional)</span>
                    </label>
                    <input type="text" wire:model.live="domain" placeholder="contoh: app.domain.com"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-gray-500 focus:ring-0 focus:outline-none">
                    @error('domain')
                        <div class="text-xs text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ACTIVE TOGGLE --}}
                <div class="sm:col-span-2 flex items-center justify-between gap-4 pt-2">
                    <div>
                        <div class="text-sm font-medium text-slate-900">Status</div>
                        <div class="text-xs text-slate-500">Aktifkan/nonaktifkan aplikasi</div>
                        @error('is_active')
                            <div class="text-xs text-rose-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="is_active" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-slate-200 rounded-full peer
                                    peer-checked:bg-slate-900
                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                    after:bg-white after:border after:border-slate-200
                                    after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:after:translate-x-5">
                        </div>
                    </label>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" wire:click="back"
                    class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 cursor-pointer">
                    Cancel
                </button>

                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="px-5 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </form>

</div>
