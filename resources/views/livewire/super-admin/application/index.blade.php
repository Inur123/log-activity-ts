{{-- resources/views/livewire/super-admin/application/index.blade.php --}}
<div class="space-y-4">

    {{-- Title --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Applications</h1>
            <p class="text-slate-600 text-sm">Kelola aplikasi & API Key (token).</p>
        </div>

        <button type="button" wire:click="create"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 cursor-pointer">
            <i class="fa-solid fa-plus"></i> Tambah
        </button>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6" x-data="{ open: false }">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="h-9 w-9 rounded-xl bg-slate-900 text-white flex items-center justify-center">
                    <i class="fa-solid fa-filter"></i>
                </div>
                <div>
                    <div class="font-semibold text-slate-900 leading-tight">Filters</div>
                    <div class="text-xs text-slate-500">Cari aplikasi dengan cepat</div>
                </div>
            </div>

            <button type="button"
                class="sm:hidden inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700"
                x-on:click="open = !open">
                <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                <span x-text="open ? 'Tutup' : 'Buka'"></span>
            </button>
        </div>

        <div class="mt-4" :class="open ? 'block' : 'hidden sm:block'">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">

                <div class="lg:col-span-6">
                    <label class="text-xs font-semibold text-slate-600">Search</label>
                    <div class="relative">
                        <i
                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" wire:model.live.debounce.300ms="q"
                            placeholder="name / domain / api key..."
                            class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 focus:border-gray-500 focus:ring-0 focus:outline-none" />
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Status</label>
                    <select wire:model.live="active"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white focus:border-gray-500 focus:ring-0 focus:outline-none">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Stack</label>
                    <select wire:model.live="stack"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white focus:border-gray-500 focus:ring-0 focus:outline-none">
                        <option value="">All</option>
                        <option value="laravel">laravel</option>
                        <option value="codeigniter">codeigniter</option>
                        <option value="django">django</option>
                        <option value="other">other</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Per Page</label>
                    <select wire:model.live="per_page"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white focus:border-gray-500 focus:ring-0 focus:outline-none">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

            </div>

            <div class="mt-4 flex justify-between text-xs text-slate-500">
                <div wire:loading>
                    <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                </div>
                <div>Total: {{ $total }} • Page {{ $page }} / {{ $lastPage }}</div>
            </div>
        </div>
    </div>

    {{-- TABLE (tanpa tampil API key) --}}
    <div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
        <div class="min-w-[1100px]">

            <div class="grid grid-cols-12 bg-slate-50 text-slate-600 border-b border-slate-200">
                <div class="col-span-1 px-6 py-3 text-sm font-semibold">No</div>
                <div class="col-span-5 px-6 py-3 text-sm font-semibold">Name</div>
                <div class="col-span-2 px-6 py-3 text-sm font-semibold">Stack</div>
                <div class="col-span-2 px-6 py-3 text-sm font-semibold">Status</div>
                <div class="col-span-2 px-6 py-3 text-sm font-semibold">Aksi</div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($apps as $app)
                    @php $no = ($page - 1) * $per_page + $loop->iteration; @endphp

                    <div class="grid grid-cols-12 hover:bg-slate-50 transition">
                        <div class="col-span-1 px-6 py-4">
                            <div class="font-bold text-slate-900">{{ $no }}</div>
                        </div>

                        <div class="col-span-5 px-6 py-4 min-w-0">
                            <div class="font-semibold text-slate-900 truncate">{{ $app->name }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ $app->domain ?? '-' }}</div>
                        </div>

                        <div class="col-span-2 px-6 py-4">
                            <span
                                class="inline-flex px-2 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 text-xs">
                                {{ $app->stack }}
                            </span>
                        </div>

                        <div class="col-span-2 px-6 py-4">
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

                        <div class="col-span-2 px-6 py-4 flex justify-end gap-2">
                            <button type="button" wire:click="detail(@js($app->id))"
                                class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm hover:bg-slate-50 cursor-pointer">
                                Detail
                            </button>

                            <button type="button" wire:click="edit(@js($app->id))"
                                class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm hover:bg-slate-800 cursor-pointer">
                                Edit
                            </button>

                            <button type="button"
                                @click="$dispatch('confirm-delete', { id: @js($app->id), name: @js($app->name) })"
                                class="px-3 py-2 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 text-sm hover:bg-rose-100 cursor-pointer">
                                Delete
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-slate-500">
                        Tidak ada aplikasi ditemukan.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Pagination --}}
        @if ($lastPage > 1)
            @php
                $current = $page;
                $last = $lastPage;
                $start = max(1, $current - 2);
                $end = min($last, $current + 2);
            @endphp

            <div class="border-t border-slate-200 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    <div class="text-xs text-slate-500">
                        Page <span class="font-semibold text-slate-700">{{ $current }}</span>
                        of <span class="font-semibold text-slate-700">{{ $last }}</span>
                        • Total <span class="font-semibold text-slate-700">{{ $total }}</span>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-2">

                        <button type="button" wire:click="prevPage" @disabled($current <= 1)
                            class="h-10 inline-flex items-center gap-2 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-left"></i> Prev
                        </button>

                        <div class="hidden sm:flex items-center gap-1">
                            @if ($start > 1)
                                <button wire:click="gotoPage(1, {{ $last }})"
                                    class="h-10 w-10 inline-flex items-center justify-center rounded-xl border bg-white hover:bg-slate-50 text-sm ">1</button>
                                @if ($start > 2)
                                    <span class="px-2 text-slate-400">…</span>
                                @endif
                            @endif

                            @for ($p = $start; $p <= $end; $p++)
                                @if ($p === $current)
                                    <span
                                        class="h-10 w-10 inline-flex items-center justify-center rounded-xl bg-slate-900 text-white text-sm">{{ $p }}</span>
                                @else
                                    <button wire:click="gotoPage({{ $p }}, {{ $last }})"
                                        class="h-10 w-10 inline-flex items-center justify-center rounded-xl border bg-white hover:bg-slate-50 text-sm">{{ $p }}</button>
                                @endif
                            @endfor

                            @if ($end < $last)
                                @if ($end < $last - 1)
                                    <span class="px-2 text-slate-400">…</span>
                                @endif
                                <button wire:click="gotoPage({{ $last }}, {{ $last }})"
                                    class="h-10 w-10 inline-flex items-center justify-center rounded-xl border bg-white hover:bg-slate-50 text-sm">{{ $last }}</button>
                            @endif
                        </div>

                        <button type="button" wire:click="nextPage({{ $last }})"
                            @disabled($current >= $last)
                            class="h-10 inline-flex items-center gap-2 px-4 rounded-xl border bg-white hover:bg-slate-50 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </button>

                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ✅ Confirm Delete Modal (harus di dalam root) --}}
    {{-- ✅ Confirm Delete Modal (harus di dalam root) --}}
<div
    x-data="{
        open: false,
        id: null,
        name: '',
        loading: false,
        show(p) {
            this.id = p.id;
            this.name = p.name || '';
            this.open = true;

            // auto focus tombol konfirmasi (UX umum)
            this.$nextTick(() => this.$refs.confirmBtn?.focus());
        },
        close() {
            if (this.loading) return;
            this.open = false;
            this.id = null;
            this.name = '';
        },
        async confirm() {
            if (!this.id || this.loading) return;
            this.loading = true;

            const idToDelete = this.id;

            // hide cepat
            this.open = false;
            this.id = null;
            this.name = '';

            try {
                await $wire.call('delete', idToDelete);
            } finally {
                this.loading = false;
            }
        }
    }"
    x-on:confirm-delete.window="show($event.detail)"
    x-on:keydown.escape.window="open && close()"
    x-on:keydown.enter.window.prevent="open && confirm()"
>
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[9998] bg-black/40"
        @click="close()"
        style="display:none;"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        style="display:none;"
    >
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden" @click.stop>
            <div class="p-5 flex items-start gap-3">
                <div class="h-10 w-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="text-lg font-bold text-slate-900">Hapus application?</div>
                    <div class="mt-1 text-sm text-slate-600">
                        Kamu yakin mau hapus
                        <span class="font-semibold text-slate-900" x-text="name || 'application ini'"></span>?
                        Tindakan ini tidak bisa dibatalkan.
                    </div>
                </div>
            </div>

            <div class="px-5 pb-5 flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 cursor-pointer"
                    @click="close()"
                    :disabled="loading"
                >
                    Batal
                </button>

                <button
                    x-ref="confirmBtn"
                    type="button"
                    class="h-10 px-4 rounded-xl bg-rose-600 text-white hover:bg-rose-700 inline-flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                    @click="confirm()"
                    :disabled="loading"
                >
                    <i class="fa-solid fa-trash"></i>
                    <span x-text="loading ? 'Menghapus...' : 'Ya, hapus'"></span>
                </button>
            </div>
        </div>
    </div>
</div>



</div>
