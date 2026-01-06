<div class="space-y-4">

    {{-- Title --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Log Viewer</h1>
            <p class="text-slate-600 text-sm">Halaman Log Viewer untuk Super Admin.</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6" x-data="{ open: false }">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="h-9 w-9 rounded-xl bg-slate-900 text-white flex items-center justify-center">
                    <i class="fa-solid fa-filter"></i>
                </div>
                <div>
                    <div class="font-semibold text-slate-900 leading-tight">Filters</div>
                    <div class="text-xs text-slate-500">Cari log dengan cepat</div>
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

                {{-- Search --}}
                <div class="lg:col-span-5">
                    <label class="text-xs font-semibold text-slate-600">Search</label>
                    <div class="relative">
                        <i
                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" wire:model.live.debounce.300ms="q"
                            placeholder="ID / payload / nama aplikasi..."
                            class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 focus:border-gray-500 focus:ring-0 focus:outline-none" />
                    </div>
                </div>

                {{-- App --}}
                <div class="lg:col-span-3">
                    <label class="text-xs font-semibold text-slate-600">Application</label>
                    <select wire:model.live="application_id"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white focus:border-gray-500 focus:ring-0 focus:outline-none">
                        <option value="">All</option>
                        @foreach ($applications as $app)
                            <option value="{{ $app->id }}">{{ $app->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Log Type --}}
                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Log Type</label>
                    <select wire:model.live="log_type"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white focus:border-gray-500 focus:ring-0 focus:outline-none">
                        <option value="">All</option>
                        @foreach ($logTypeOptions as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Per Page --}}
                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Per Page</label>
                    <select wire:model.live.number="per_page"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white focus:border-gray-500 focus:ring-0 focus:outline-none">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                {{-- From --}}
                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">From</label>
                    <input type="date" wire:model.live="from"
                        class="w-full py-2.5 rounded-xl border border-slate-200 focus:border-gray-500 focus:ring-0 focus:outline-none">
                </div>

                {{-- To --}}
                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">To</label>
                    <input type="date" wire:model.live="to"
                        class="w-full py-2.5 rounded-xl border border-slate-200 focus:border-gray-500 focus:ring-0 focus:outline-none">
                </div>

                {{-- Sort --}}
                <div class="lg:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Sort</label>
                    <select wire:model.live="sort"
                        class="w-full py-2.5 rounded-xl border border-slate-200 bg-white focus:border-gray-500 focus:ring-0 focus:outline-none">
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
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

    {{-- ✅ SECURITY CHECK (VERIFY HASH CHAIN) --}}
   <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        {{-- Left --}}
        <div class="flex items-start sm:items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <div class="min-w-0">
                <div class="font-semibold text-slate-900">Security Check</div>
                <div class="text-xs text-slate-500">
                    Verifikasi hash chain untuk Application yang dipilih
                </div>
            </div>
        </div>

        {{-- Right Buttons --}}
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">

            {{-- Verify --}}
            <button type="button"
                wire:click="verifySelectedApplicationChain"
                class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                @disabled($application_id === '' || $verifying)>
                @if ($verifying)
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Verifying...</span>
                @else
                    <i class="fa-solid fa-shield-check"></i>
                    <span>Verify Now</span>
                @endif
            </button>

            {{-- Clear --}}
            <button type="button"
                wire:click="clearChainStatus"
                class="w-full sm:w-auto px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                @disabled(!$chainStatus)>
                <i class="fa-solid fa-xmark"></i>
                <span>Clear</span>
            </button>

        </div>

    </div>

    {{-- Info --}}
    @if ($application_id === '')
        <div class="mt-3 text-xs text-slate-500 flex items-center gap-2">
            <i class="fa-solid fa-circle-info"></i>
            <span>Pilih Application terlebih dahulu untuk melakukan verifikasi hash chain.</span>
        </div>
    @endif

    {{-- Error List --}}
    @if (!empty($chainStatus['errors']))
        <details class="mt-3 rounded-xl border border-slate-200 bg-white overflow-hidden">
            <summary
                class="cursor-pointer px-4 sm:px-6 py-4 flex items-center justify-between gap-3 font-semibold text-slate-900 hover:bg-slate-50">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                    <span>Lihat detail error ({{ count($chainStatus['errors']) }})</span>
                </div>
                <i class="fa-solid fa-chevron-down text-slate-500"></i>
            </summary>

            <div class="p-4 sm:p-6 space-y-4 bg-slate-50">
                @foreach ($chainStatus['errors'] as $err)
                    <div class="rounded-xl border border-rose-200 bg-white p-4 space-y-3">

                        {{-- Header --}}
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                    <i class="fa-solid fa-xmark text-rose-600"></i>
                                    <span>Hash Mismatch (Seq #{{ $err['seq'] ?? '-' }})</span>
                                </div>

                                <div class="text-xs text-slate-500 break-all">
                                    Log UUID:
                                    <span class="font-mono text-slate-700">{{ $err['log_id'] ?? '-' }}</span>
                                </div>
                            </div>

                            <span
                                class="self-start px-2 py-1 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                                {{ $err['type'] ?? 'error' }}
                            </span>
                        </div>

                        {{-- Meta --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs text-slate-500">Log Type</div>
                                <div class="font-semibold text-slate-900 break-all">
                                    {{ $err['log_type'] ?? '-' }}
                                </div>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs text-slate-500">Created At</div>
                                <div class="font-semibold text-slate-900 break-all">
                                    {{ $err['created_at'] ?? '-' }}
                                </div>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs text-slate-500">Seq</div>
                                <div class="font-bold text-slate-900">
                                    {{ $err['seq'] ?? '-' }}
                                </div>
                            </div>
                        </div>

                        {{-- Hash Compare --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <div class="text-xs text-slate-500 mb-1">Expected Hash</div>
                                <pre class="p-3 rounded-xl bg-slate-900 text-slate-100 text-xs overflow-x-auto break-all whitespace-pre-wrap">{{ $err['expected'] ?? '-' }}</pre>
                            </div>

                            <div>
                                <div class="text-xs text-slate-500 mb-1">Stored Hash</div>
                                <pre class="p-3 rounded-xl bg-slate-900 text-slate-100 text-xs overflow-x-auto break-all whitespace-pre-wrap">{{ $err['found'] ?? '-' }}</pre>
                            </div>
                        </div>

                        {{-- Payload --}}
                        @if (!empty($err['payload_preview']))
                            <details class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                                <summary
                                    class="cursor-pointer px-4 py-3 flex items-center justify-between text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-database text-slate-500"></i>
                                        Payload Preview
                                    </div>
                                    <i class="fa-solid fa-chevron-down text-slate-500"></i>
                                </summary>

                                <div class="p-4">
                                    <pre class="p-3 rounded-xl bg-slate-900 text-slate-100 text-xs overflow-x-auto break-all whitespace-pre-wrap">{{ $err['payload_preview'] }}</pre>
                                </div>
                            </details>
                        @endif

                    </div>
                @endforeach
            </div>
        </details>
    @endif

</div>



    {{-- TABLE --}}
    <div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
        <div class="min-w-[980px]">

            {{-- Header --}}
            <div class="grid grid-cols-12 bg-slate-50 text-slate-600 border-b border-slate-200">
                <div class="col-span-1 px-6 py-3 text-sm font-semibold">No</div>
                <div class="col-span-2 px-6 py-3 text-sm font-semibold">Application</div>
                <div class="col-span-2 px-6 py-3 text-sm font-semibold">Type</div>
                <div class="col-span-5 px-6 py-3 text-sm font-semibold">Payload</div>
                <div class="col-span-2 px-6 py-3 text-sm font-semibold">Aksi</div>
            </div>

            {{-- Body --}}
            <div class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    @php
                        $payloadPreview = is_string($log->payload)
                            ? $log->payload
                            : json_encode($log->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        $no = ($page - 1) * $per_page + $loop->iteration;
                    @endphp

                    <div class="grid grid-cols-12 hover:bg-slate-50 transition">

                        <div class="col-span-1 px-6 py-4">
                            <div class="font-bold text-slate-900">{{ $no }}</div>
                        </div>

                        <div class="col-span-2 px-6 py-4 min-w-0">
                            <div class="font-semibold text-slate-900 truncate"
                                title="{{ $log->application->name ?? '-' }}">
                                {{ $log->application->name ?? '-' }}
                            </div>
                        </div>

                        <div class="col-span-2 px-6 py-4 min-w-0">
                            <span
                                class="inline-block max-w-full px-2 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 text-xs whitespace-normal break-all">
                                {{ $log->log_type ?: '-' }}
                            </span>
                        </div>

                        <div class="col-span-5 px-6 py-4 min-w-0">
                            <button type="button" wire:click="showDetail(@js($log->id))"
                                class="w-full text-left text-xs font-mono text-slate-600 truncate hover:underline hover:text-slate-900 cursor-pointer">
                                {{ $payloadPreview }}
                            </button>
                        </div>

                        <div class="col-span-2 px-6 py-4">
                            <button type="button" wire:click="showDetail(@js($log->id))"
                                class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm hover:bg-slate-800 cursor-pointer">
                                Detail
                            </button>
                        </div>

                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-slate-500">
                        Tidak ada log ditemukan.
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
                            class="h-10 inline-flex items-center gap-2 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <i class="fa-solid fa-chevron-left"></i>
                            Prev
                        </button>

                        <div class="hidden sm:flex items-center gap-1">
                            @if ($start > 1)
                                <button wire:click="gotoPage(1, {{ $last }})"
                                    class="h-10 w-10 inline-flex items-center justify-center rounded-xl border bg-white hover:bg-slate-50 text-sm">
                                    1
                                </button>
                                @if ($start > 2)
                                    <span class="px-2 text-slate-400">…</span>
                                @endif
                            @endif

                            @for ($p = $start; $p <= $end; $p++)
                                @if ($p === $current)
                                    <span
                                        class="h-10 w-10 inline-flex items-center justify-center rounded-xl bg-slate-900 text-white text-sm">
                                        {{ $p }}
                                    </span>
                                @else
                                    <button wire:click="gotoPage({{ $p }}, {{ $last }})"
                                        class="h-10 w-10 inline-flex items-center justify-center rounded-xl border bg-white hover:bg-slate-50 text-sm cursor-pointer">
                                        {{ $p }}
                                    </button>
                                @endif
                            @endfor

                            @if ($end < $last)
                                @if ($end < $last - 1)
                                    <span class="px-2 text-slate-400">…</span>
                                @endif

                                <button wire:click="gotoPage({{ $last }}, {{ $last }})"
                                    class="h-10 w-10 inline-flex items-center justify-center rounded-xl border bg-white hover:bg-slate-50 text-sm">
                                    {{ $last }}
                                </button>
                            @endif
                        </div>

                        <button type="button" wire:click="nextPage({{ $last }})"
                            @disabled($current >= $last)
                            class="h-10 inline-flex items-center gap-2 px-4 rounded-xl border bg-white hover:bg-slate-50 text-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            Next
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                    </div>

                </div>
            </div>
        @endif

    </div>

</div>
