@extends('layouts.app')

@section('title', 'Log System Viewer')
@section('breadcrumb', 'Dashboard')
@section('page_title', 'Log System Viewer')

@section('content')

@php
    $applications = $applications ?? \App\Models\Application::orderBy('name')->get();

    $typeMeta = [
        'activity' => [
            'label' => 'Activity',
            'icon'  => 'fa-user',
            'badge' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
            'dot'   => 'bg-blue-500',
        ],
        'audit_trail' => [
            'label' => 'Audit Trail',
            'icon'  => 'fa-clock-rotate-left',
            'badge' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
            'dot'   => 'bg-emerald-500',
        ],
        'authentication' => [
            'label' => 'Authentication',
            'icon'  => 'fa-right-to-bracket',
            'badge' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
            'dot'   => 'bg-rose-500',
        ],
        'system' => [
            'label' => 'System',
            'icon'  => 'fa-gear',
            'badge' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
            'dot'   => 'bg-violet-500',
        ],
        'custom' => [
            'label' => 'Custom',
            'icon'  => 'fa-puzzle-piece',
            'badge' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
            'dot'   => 'bg-amber-500',
        ],
    ];

    $normalizeType = function (?string $type) {
        if ($type === 'security') return 'authentication';
        return $type ?: 'custom';
    };

    $getMeta = function ($type) use ($typeMeta, $normalizeType) {
        $t = $normalizeType($type);
        return $typeMeta[$t] ?? $typeMeta['custom'];
    };

    $pick = function (array $payload, array $paths, $default = null) {
        foreach ($paths as $p) {
            $v = data_get($payload, $p);
            if ($v !== null && $v !== '' && $v !== []) return $v;
        }
        return $default;
    };

    $chip = function (string $icon, $text) {
        $text = is_string($text) ? \Illuminate\Support\Str::limit($text, 38) : $text;
        return '<span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200">
                    <i class="fa-solid '.$icon.' text-slate-400"></i>
                    <span class="truncate">'.$text.'</span>
                </span>';
    };

    // ====== NEW: Convert semua JSON => readable key/value ======
    $isSensitiveKey = function (string $key) {
        $key = strtolower($key);
        $blocked = [
            'password','pass','token','access_token','refresh_token','jwt','signature',
            'authorization','secret','api_key','apikey','private_key','client_secret',
            'session','cookie',
        ];
        foreach ($blocked as $b) {
            if (str_contains($key, $b)) return true;
        }
        return false;
    };

    $fmt = function ($v, int $limit = 220) {
        if ($v === null) return null;
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_numeric($v)) return (string) $v;
        if (is_string($v)) return \Illuminate\Support\Str::limit($v, $limit);
        if (is_array($v)) {
            $j = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            return \Illuminate\Support\Str::limit($j ?: '[]', $limit);
        }
        return \Illuminate\Support\Str::limit((string) $v, $limit);
    };

    // Flatten recursive: key.path => value
    $flatten = function ($data, string $prefix = '') use (&$flatten) {
        $out = [];

        if (!is_array($data)) return $out;

        foreach ($data as $k => $v) {
            $k = (string) $k;
            $key = $prefix === '' ? $k : $prefix.'.'.$k;

            if (is_array($v)) {
                if ($v === []) {
                    $out[$key] = [];
                } else {
                    foreach ($flatten($v, $key) as $kk => $vv) {
                        $out[$kk] = $vv;
                    }
                }
            } else {
                $out[$key] = $v;
            }
        }

        return $out;
    };

    $kvRow = function (string $k, $v) use ($fmt) {
        $vv = $fmt($v);
        if ($vv === null || $vv === '') return '';
        return '<div class="grid grid-cols-12 gap-3 py-1">
                    <div class="col-span-5 sm:col-span-4 text-[11px] font-semibold text-slate-500 break-words">'.$k.'</div>
                    <div class="col-span-7 sm:col-span-8 text-[12px] text-slate-800 break-words">'.$vv.'</div>
                </div>';
    };
@endphp

<style>
    summary::-webkit-details-marker { display:none; }
    pre { white-space: pre; }
    .code-scroll::-webkit-scrollbar { width: 10px; height: 10px; }
    .code-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
    .code-scroll::-webkit-scrollbar-track { background: transparent; }
</style>

<div class="mx-auto max-w-7xl space-y-6">

    {{-- HEADER (responsive) --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-4 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Log System Viewer</h1>
                <p class="text-sm text-slate-600">Monitoring & debugging log aplikasi</p>
            </div>

            <button id="refreshNow"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                <i class="fa-solid fa-rotate-right"></i> Refresh
            </button>
        </div>
    </div>

    {{-- STATS (responsive text) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl bg-blue-50 ring-1 ring-blue-100 p-4">
            <div class="text-sm font-semibold text-blue-700">Total Logs</div>
            <div class="mt-1 text-2xl sm:text-3xl font-bold text-blue-900">{{ \App\Models\UnifiedLog::count() }}</div>
        </div>

        <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-100 p-4">
            <div class="text-sm font-semibold text-emerald-700">Applications</div>
            <div class="mt-1 text-2xl sm:text-3xl font-bold text-emerald-900">{{ \App\Models\Application::count() }}</div>
        </div>

        <div class="rounded-xl bg-violet-50 ring-1 ring-violet-100 p-4">
            <div class="text-sm font-semibold text-violet-700">Today</div>
            <div class="mt-1 text-2xl sm:text-3xl font-bold text-violet-900">{{ \App\Models\UnifiedLog::whereDate('created_at', today())->count() }}</div>
        </div>

        <div class="rounded-xl bg-rose-50 ring-1 ring-rose-100 p-4">
            <div class="text-sm font-semibold text-rose-700">Auth Logs</div>
            <div class="mt-1 text-2xl sm:text-3xl font-bold text-rose-900">
                {{ \App\Models\UnifiedLog::whereIn('log_type', ['authentication','security'])->count() }}
            </div>
        </div>
    </div>

    {{-- FILTER (grid responsive) --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-4">
        <form method="GET" action="{{ request()->url() }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3 items-end">

            <div class="xl:col-span-2">
                <label class="text-xs font-semibold text-slate-600">Search</label>
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="ID, payload, application..."
                    class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Application</label>
                <select name="application_id" class="w-full rounded-lg border px-3 py-2 text-sm">
                    <option value="">All Applications</option>
                    @foreach ($applications as $app)
                        <option value="{{ $app->id }}" {{ request('application_id') == $app->id ? 'selected' : '' }}>
                            {{ $app->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Type</label>
                <select name="log_type" class="w-full rounded-lg border px-3 py-2 text-sm">
                    <option value="">All Types</option>
                    <option value="activity" {{ request('log_type') == 'activity' ? 'selected' : '' }}>Activity</option>
                    <option value="audit_trail" {{ request('log_type') == 'audit_trail' ? 'selected' : '' }}>Audit Trail</option>
                    <option value="authentication" {{ in_array(request('log_type'), ['authentication','security']) ? 'selected' : '' }}>Authentication</option>
                    <option value="system" {{ request('log_type') == 'system' ? 'selected' : '' }}>System</option>
                    <option value="custom" {{ request('log_type') == 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Sort</label>
                <select name="sort" class="w-full rounded-lg border px-3 py-2 text-sm">
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Per Page</label>
                <select name="per_page" class="w-full rounded-lg border px-3 py-2 text-sm">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ request('per_page', 25) == $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2 md:col-span-2 xl:col-span-6">
                <button type="submit"
                    class="w-full sm:w-auto rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">
                    Filter
                </button>
                <a href="{{ request()->url() }}"
                    class="w-full sm:w-auto text-center rounded-lg border px-4 py-2 text-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- TABLE LOG --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="border-b border-gray-200 p-4 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-slate-900">Recent Logs</h2>
                <div class="text-xs text-slate-500">Klik “Details JSON” untuk lihat JSON mentah. Bagian “All Fields” adalah versi readable (SEMUA key).</div>
            </div>
            <div class="text-xs text-slate-600">
                Total: <span class="font-semibold">{{ $logs->total() }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-bold text-slate-600 whitespace-nowrap">No</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-bold text-slate-600 whitespace-nowrap hidden sm:table-cell">Application</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-bold text-slate-600 whitespace-nowrap">Type</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-bold text-slate-600">Payload</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-bold text-slate-600 whitespace-nowrap hidden sm:table-cell">Time</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($logs as $log)
                        @php
                            $displayType = $normalizeType($log->log_type);
                            $meta = $getMeta($log->log_type);

                            $payload = is_array($log->payload)
                                ? $log->payload
                                : (json_decode($log->payload ?? '[]', true) ?? []);

                            $title = $pick($payload, ['event','action','message','name','title'], 'Log Data');

                            $actor  = $pick($payload, ['user.email','user.name','user.username','actor.email','actor.name','username','email','user_id','actor_id']);
                            $ip     = $pick($payload, ['ip','client_ip','request.ip','meta.ip']);
                            $path   = $pick($payload, ['path','url','request.url','request.path','route','endpoint']);
                            $method = $pick($payload, ['method','request.method']);
                            $status = $pick($payload, ['status','code','http_status','response.status']);
                            $msg    = $pick($payload, ['description','detail','reason','error','exception','message']);

                            $keys = collect(array_keys($payload))
                                ->reject(fn($k) => in_array($k, ['created_at','updated_at','token','signature','jwt','password']))
                                ->take(6)->values()->all();

                            $pretty = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?? '{}';

                            // ✅ ALL FIELDS flatten + sort
                            $flat = $flatten($payload);
                            ksort($flat);

                            $appName = optional($log->application)->name ?? '-';
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 sm:px-6 py-4 text-sm font-semibold text-slate-700 align-top">
                                {{ $logs->firstItem() + $loop->index }}
                                <div class="text-xs text-slate-500 font-normal">#{{ $log->id }}</div>
                            </td>

                            <td class="px-4 sm:px-6 py-4 align-top hidden sm:table-cell">
                                <div class="text-sm font-semibold text-slate-900">{{ $appName }}</div>
                                <div class="text-xs text-slate-500">{{ optional($log->application)->stack ?? '-' }}</div>
                            </td>

                            <td class="px-4 sm:px-6 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $meta['dot'] }}"></span>
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold {{ $meta['badge'] }}">
                                        <i class="fa-solid {{ $meta['icon'] }}"></i>
                                        {{ $meta['label'] }}
                                    </span>
                                </div>
                                <div class="mt-1 text-[11px] text-slate-500 font-mono">type: {{ $displayType }}</div>
                            </td>

                            <td class="px-4 sm:px-6 py-4 align-top">
                                <div class="space-y-2">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-slate-900 truncate">{{ $title }}</div>
                                            <div class="text-xs text-slate-500">
                                                Keys: {{ implode(', ', $keys) }}{{ count($payload) > count($keys) ? ', …' : '' }}
                                                <span class="ml-2 text-slate-400">|</span>
                                                <span class="ml-2">All fields: <span class="font-semibold">{{ count($flat) }}</span></span>
                                            </div>
                                        </div>

                                        <div class="flex gap-2 shrink-0">
                                            <button type="button"
                                                class="copy-json inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
                                                title="Copy JSON">
                                                <i class="fa-regular fa-copy"></i> Copy
                                            </button>

                                            <button type="button"
                                                class="copy-hash inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50"
                                                data-hash="{{ $log->hash ?? '' }}"
                                                title="Copy hash">
                                                <i class="fa-solid fa-fingerprint text-slate-400"></i>
                                                {{ $log->hash ? substr($log->hash, 0, 8).'…' : '-' }}
                                            </button>
                                        </div>
                                    </div>

                                    {{-- chips lama tetap --}}
                                    <div class="flex flex-wrap gap-2">
                                        @if($actor) {!! $chip('fa-user', $actor) !!} @endif
                                        @if($ip) {!! $chip('fa-network-wired', $ip) !!} @endif
                                        @if($method || $path) {!! $chip('fa-link', trim(($method ? strtoupper($method) : '').' '.($path ?? ''))) !!} @endif
                                        @if($status !== null) {!! $chip('fa-circle-info', $status) !!} @endif
                                        @if($msg && $msg !== $title) {!! $chip('fa-message', $msg) !!} @endif

                                        <span class="sm:hidden">{!! $chip('fa-cube', $appName) !!}</span>
                                        <span class="sm:hidden">{!! $chip('fa-calendar-day', $log->created_at->format('Y-m-d H:i')) !!}</span>
                                    </div>

                                    {{-- ✅ NEW: All Fields Readable (SEMUA KEY) - desain masih selaras --}}
                                    <details class="group">
                                        <summary class="cursor-pointer select-none rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 flex items-center justify-between">
                                            <span class="inline-flex items-center gap-2">
                                                <i class="fa-solid fa-list"></i> All Fields (Readable)
                                            </span>
                                            <span class="inline-flex items-center gap-2 text-slate-500">
                                                <span class="group-open:hidden">Expand</span>
                                                <span class="hidden group-open:inline">Collapse</span>
                                                <i class="fa-solid fa-chevron-down transition group-open:rotate-180"></i>
                                            </span>
                                        </summary>

                                        <div class="mt-2 rounded-xl bg-white ring-1 ring-slate-200">
                                            <div class="max-h-72 overflow-auto px-3 py-2">
                                                @php $rendered = 0; @endphp

                                                @foreach($flat as $k => $v)
                                                    @php
                                                        if ($isSensitiveKey($k)) {
                                                            $v = '••••••';
                                                        }
                                                        $row = $kvRow($k, $v);
                                                    @endphp
                                                    @if($row)
                                                        {!! $row !!}
                                                        @php $rendered++; @endphp
                                                    @endif
                                                @endforeach

                                                @if($rendered === 0)
                                                    <div class="py-3 text-xs text-slate-500">No fields</div>
                                                @endif
                                            </div>
                                        </div>
                                    </details>

                                    {{-- Raw JSON tetap --}}
                                    <details class="group">
                                        <summary class="cursor-pointer select-none rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100 flex items-center justify-between">
                                            <span class="inline-flex items-center gap-2">
                                                <i class="fa-solid fa-brackets-curly"></i> Details JSON
                                            </span>
                                            <span class="inline-flex items-center gap-2 text-slate-500">
                                                <span class="group-open:hidden">Expand</span>
                                                <span class="hidden group-open:inline">Collapse</span>
                                                <i class="fa-solid fa-chevron-down transition group-open:rotate-180"></i>
                                            </span>
                                        </summary>

                                        <pre class="log-json code-scroll mt-2 max-h-64 overflow-auto text-xs bg-slate-900 text-white rounded-xl p-3 ring-1 ring-slate-200">{{ $pretty }}</pre>
                                    </details>
                                </div>
                            </td>

                            <td class="px-4 sm:px-6 py-4 text-sm align-top hidden sm:table-cell">
                                <div class="font-semibold text-slate-900">{{ $log->created_at->format('Y-m-d') }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-slate-500">No logs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="text-sm text-slate-600">
                    Showing <span class="font-semibold">{{ $logs->firstItem() }}</span>
                    to <span class="font-semibold">{{ $logs->lastItem() }}</span>
                    of <span class="font-semibold">{{ $logs->total() }}</span> results
                </div>

                <div class="overflow-x-auto">
                    <div class="flex items-center gap-1">
                        <a href="{{ $logs->previousPageUrl() }}"
                           class="px-3 py-1 rounded-lg text-sm {{ $logs->onFirstPage() ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white border hover:bg-slate-50 border-gray-200' }}">
                            Prev
                        </a>

                        @php
                            $current = $logs->currentPage();
                            $last = $logs->lastPage();
                            $start = max(1, $current - 2);
                            $end = min($last, $current + 2);
                        @endphp

                        @if ($start > 1)
                            <a href="{{ $logs->url(1) }}" class="px-3 py-1 rounded-lg text-sm bg-white border hover:bg-slate-50 border-gray-200">1</a>
                            @if ($start > 2) <span class="px-2 text-slate-400">...</span> @endif
                        @endif

                        @for ($i = $start; $i <= $end; $i++)
                            <a href="{{ $logs->url($i) }}"
                               class="px-3 py-1 rounded-lg text-sm {{ $current == $i ? 'bg-slate-900 text-white' : 'bg-white border hover:bg-slate-50 border-gray-200' }}">
                                {{ $i }}
                            </a>
                        @endfor

                        @if ($end < $last)
                            @if ($end < $last - 1) <span class="px-2 text-slate-400">...</span> @endif
                            <a href="{{ $logs->url($last) }}" class="px-3 py-1 rounded-lg text-sm bg-white border hover:bg-slate-50 border-gray-200">{{ $last }}</a>
                        @endif

                        <a href="{{ $logs->nextPageUrl() }}"
                           class="px-3 py-1 rounded-lg text-sm {{ $logs->hasMorePages() ? 'bg-white border hover:bg-slate-50 border-gray-200' : 'bg-slate-100 text-slate-400 cursor-not-allowed' }}">
                            Next
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Toast --}}
<div id="toast"
     class="fixed bottom-5 left-1/2 -translate-x-1/2 hidden rounded-full bg-slate-900 text-white px-4 py-2 text-sm shadow-lg ring-1 ring-slate-700 z-50">
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toastEl = document.getElementById('toast');
    let toastTimer = null;

    function toast(msg) {
        if (toastTimer) clearTimeout(toastTimer);
        toastEl.textContent = msg;
        toastEl.classList.remove('hidden');
        toastTimer = setTimeout(() => toastEl.classList.add('hidden'), 1400);
    }

    document.querySelectorAll('.copy-json').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const button = e.target.closest('.copy-json') || btn;
            const row = button.closest('tr');
            const pre = row?.querySelector('.log-json');
            if (!pre) return;

            try {
                await navigator.clipboard.writeText(pre.textContent);
                toast('JSON copied');
            } catch (err) {
                toast('Copy failed');
            }
        });
    });

    document.querySelectorAll('.copy-hash').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const button = e.target.closest('.copy-hash') || btn;
            const hash = button.getAttribute('data-hash') || '';
            try {
                await navigator.clipboard.writeText(hash);
                toast('Hash copied');
            } catch (err) {
                toast('Copy failed');
            }
        });
    });

    document.getElementById('refreshNow')?.addEventListener('click', () => {
        window.location.reload();
    });
});
</script>

@endsection
