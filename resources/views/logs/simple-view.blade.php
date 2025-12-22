{{-- resources/views/logs/simple-view.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Log Viewer - Development</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        summary::-webkit-details-marker { display:none; }
        pre { white-space: pre; }
        .code-scroll::-webkit-scrollbar { width: 10px; height: 10px; }
        .code-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .code-scroll::-webkit-scrollbar-track { background: transparent; }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-b from-slate-50 to-white text-slate-800">
@php
    // Kalau kamu sudah inject $applications dari controller, hapus baris ini.
    $applications = $applications ?? \App\Models\Application::query()->orderBy('name')->get();

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
        'security' => [
            'label' => 'Security',
            'icon'  => 'fa-shield-halved',
            'badge' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
            'dot'   => 'bg-red-500',
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

    $getMeta = function ($type) use ($typeMeta) {
        return $typeMeta[$type] ?? $typeMeta['custom'];
    };

    $q = request('q');
    $selectedApp = request('application_id');
    $selectedType = request('log_type');
    $perPage = request('per_page', 25);
@endphp

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">

    {{-- Top Header --}}
    <div class="mb-6 rounded-2xl bg-white/90 backdrop-blur shadow-sm ring-1 ring-slate-200">
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-slate-900 text-white flex items-center justify-center">
                            <i class="fa-solid fa-database"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                                Log System Viewer
                            </h1>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                    <i class="fa-solid fa-code"></i>
                                    Development Mode
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                    <i class="fa-solid fa-lock-open"></i>
                                    No Authentication Required
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button id="refreshNow"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400">
                        <i class="fa-solid fa-rotate-right"></i>
                        Refresh
                    </button>

                    <a href="{{ url('/logs/view') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Reset Filter
                    </a>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="rounded-2xl bg-gradient-to-b from-blue-50 to-white ring-1 ring-blue-100 p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-blue-700">Total Logs</div>
                        <div class="mt-1 text-3xl font-bold text-blue-900">
                            {{ \App\Models\UnifiedLog::count() }}
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-blue-600 text-white flex items-center justify-center">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gradient-to-b from-emerald-50 to-white ring-1 ring-emerald-100 p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-emerald-700">Applications</div>
                        <div class="mt-1 text-3xl font-bold text-emerald-900">
                            {{ \App\Models\Application::count() }}
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center">
                        <i class="fa-solid fa-cubes"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gradient-to-b from-violet-50 to-white ring-1 ring-violet-100 p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-violet-700">Today’s Logs</div>
                        <div class="mt-1 text-3xl font-bold text-violet-900">
                            {{ \App\Models\UnifiedLog::whereDate('created_at', today())->count() }}
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-violet-600 text-white flex items-center justify-center">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gradient-to-b from-amber-50 to-white ring-1 ring-amber-100 p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-semibold text-amber-800">Activity Logs</div>
                        <div class="mt-1 text-3xl font-bold text-amber-950">
                            {{ \App\Models\UnifiedLog::where('log_type', 'activity')->count() }}
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-amber-500 text-white flex items-center justify-center">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="mb-6 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="border-b border-slate-200 p-4 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Filters</h2>
                    <p class="text-sm text-slate-600">Cari cepat berdasarkan event/action/hash/payload.</p>
                </div>

                <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700 select-none">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-arrows-rotate text-slate-500"></i>
                        Auto refresh
                    </span>
                    <button id="autoRefreshToggle"
                            type="button"
                            class="relative inline-flex h-6 w-11 items-center rounded-full bg-slate-200 transition"
                            aria-pressed="false">
                        <span id="autoRefreshKnob"
                              class="inline-block h-5 w-5 translate-x-1 rounded-full bg-white shadow transition"></span>
                    </button>
                    <span class="text-xs font-medium text-slate-500" id="refreshCountdown"></span>
                </label>
            </div>
        </div>

        <form method="GET" action="{{ url('/logs/view') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="q"
                               value="{{ $q }}"
                               placeholder="Contoh: update_profile / 10.10.10.10 / hash…"
                               class="w-full rounded-xl border-slate-200 pl-10 pr-3 py-2 text-sm focus:border-slate-400 focus:ring-slate-300" />
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Application</label>
                    <select name="application_id"
                            class="w-full rounded-xl border-slate-200 py-2 text-sm focus:border-slate-400 focus:ring-slate-300">
                        <option value="">All Applications</option>
                        @foreach($applications as $app)
                            <option value="{{ $app->id }}" @selected((string)$selectedApp === (string)$app->id)>
                                {{ $app->name }} ({{ $app->stack }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Type</label>
                    <select name="log_type"
                            class="w-full rounded-xl border-slate-200 py-2 text-sm focus:border-slate-400 focus:ring-slate-300">
                        <option value="">All Types</option>
                        @foreach(['activity','audit_trail','security','system','custom'] as $t)
                            <option value="{{ $t }}" @selected((string)$selectedType === (string)$t)>
                                {{ $typeMeta[$t]['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Per page</label>
                    <select name="per_page"
                            class="w-full rounded-xl border-slate-200 py-2 text-sm focus:border-slate-400 focus:ring-slate-300">
                        @foreach([10,25,50,100] as $n)
                            <option value="{{ $n }}" @selected((int)$perPage === (int)$n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-12 flex flex-wrap gap-2 pt-1">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <i class="fa-solid fa-filter"></i>
                        Apply
                    </button>

                    <a href="{{ url('/logs/view') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Logs --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="border-b border-slate-200 p-4 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Recent Logs</h2>
                    <p class="text-sm text-slate-600">
                        Showing {{ $logs->count() }} of {{ $logs->total() }} logs
                    </p>
                </div>
                <div class="text-xs text-slate-500">
                    Tip: klik “JSON payload” untuk expand. Gunakan tombol copy untuk debugging cepat.
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 sticky top-0 z-10 backdrop-blur">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Application</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Payload</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Timestamp</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Hash</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        @php
                            $meta = $getMeta($log->log_type);

                            $payload = is_array($log->payload)
                                ? $log->payload
                                : (json_decode($log->payload ?? '[]', true) ?? []);

                            $title = data_get($payload, 'event')
                                ?? data_get($payload, 'action')
                                ?? 'Custom Data';

                            $pretty = json_encode(
                                $payload,
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                            ) ?? '{}';
                        @endphp

                        <tr class="hover:bg-slate-50/70">
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center gap-3">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $meta['dot'] }}"></span>
                                    <div>
                                        <div class="font-mono text-sm font-semibold text-slate-900">#{{ $log->id }}</div>
                                        <div class="text-xs text-slate-500">{{ $log->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-blue-50 ring-1 ring-blue-100 flex items-center justify-center text-blue-700">
                                        <i class="fa-solid fa-cube"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ optional($log->application)->name ?? 'Unknown App' }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ optional($log->application)->stack ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 align-top">
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold {{ $meta['badge'] }}">
                                    <i class="fa-solid {{ $meta['icon'] }}"></i>
                                    {{ $meta['label'] }}
                                </span>
                            </td>

                            <td class="px-6 py-4 align-top">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-slate-900 truncate">{{ $title }}</div>
                                            <div class="text-xs text-slate-500">
                                                Keys: {{ implode(', ', array_slice(array_keys($payload), 0, 6)) }}{{ count($payload) > 6 ? ', …' : '' }}
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button"
                                                    class="copy-json inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
                                                    title="Copy JSON">
                                                <i class="fa-regular fa-copy"></i>
                                                Copy
                                            </button>
                                        </div>
                                    </div>

                                    <details class="group">
                                        <summary class="cursor-pointer select-none rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100 flex items-center justify-between">
                                            <span class="inline-flex items-center gap-2">
                                                <i class="fa-solid fa-brackets-curly"></i>
                                                JSON payload
                                            </span>
                                            <span class="inline-flex items-center gap-2 text-slate-500">
                                                <span class="group-open:hidden">Expand</span>
                                                <span class="hidden group-open:inline">Collapse</span>
                                                <i class="fa-solid fa-chevron-down transition group-open:rotate-180"></i>
                                            </span>
                                        </summary>

                                        <pre class="log-json code-scroll mt-2 max-h-64 overflow-auto rounded-xl bg-slate-900 text-slate-100 p-3 text-xs ring-1 ring-slate-200">{{ $pretty }}</pre>
                                    </details>
                                </div>
                            </td>

                            <td class="px-6 py-4 align-top text-sm text-slate-600">
                                <div class="font-semibold text-slate-900">{{ $log->created_at->format('Y-m-d') }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>

                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            class="copy-hash font-mono text-xs text-slate-600 hover:text-slate-900"
                                            data-hash="{{ $log->hash }}"
                                            title="Copy hash">
                                        {{ substr($log->hash, 0, 10) }}…
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <span class="text-xs text-slate-500" title="{{ $log->hash }}">full</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                <div class="mx-auto w-fit rounded-2xl bg-slate-50 ring-1 ring-slate-200 px-6 py-5">
                                    <i class="fa-solid fa-inbox text-2xl mb-2"></i>
                                    <div class="font-semibold">No logs yet</div>
                                    <div class="text-sm">Send some API requests!</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-slate-200 px-4 sm:px-6 py-4">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Toast --}}
<div id="toast"
     class="fixed bottom-5 left-1/2 -translate-x-1/2 hidden rounded-full bg-slate-900 text-white px-4 py-2 text-sm shadow-lg ring-1 ring-slate-700">
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ===== Toast =====
    const toastEl = document.getElementById('toast');
    let toastTimer = null;

    function toast(msg) {
        if (toastTimer) clearTimeout(toastTimer);
        toastEl.textContent = msg;
        toastEl.classList.remove('hidden');
        toastTimer = setTimeout(() => toastEl.classList.add('hidden'), 1400);
    }

    // ===== Copy JSON =====
    document.querySelectorAll('.copy-json').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const row = e.target.closest('tr');
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

    // ===== Copy Hash =====
    document.querySelectorAll('.copy-hash').forEach(btn => {
        btn.addEventListener('click', async () => {
            const hash = btn.getAttribute('data-hash') || '';
            try {
                await navigator.clipboard.writeText(hash);
                toast('Hash copied');
            } catch (err) {
                toast('Copy failed');
            }
        });
    });

    // ===== Manual refresh =====
    document.getElementById('refreshNow')?.addEventListener('click', () => {
        window.location.reload();
    });

    // ===== Auto refresh toggle (30s) =====
    const toggleBtn = document.getElementById('autoRefreshToggle');
    const knob = document.getElementById('autoRefreshKnob');
    const countdownEl = document.getElementById('refreshCountdown');

    const KEY = 'logviewer_autorefresh';
    let enabled = localStorage.getItem(KEY) === '1';
    let timer = null;
    let remaining = 30;

    function renderToggle() {
        toggleBtn.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        toggleBtn.classList.toggle('bg-emerald-500', enabled);
        toggleBtn.classList.toggle('bg-slate-200', !enabled);
        knob.classList.toggle('translate-x-6', enabled);
        knob.classList.toggle('translate-x-1', !enabled);
    }

    function stopAuto() {
        if (timer) clearInterval(timer);
        timer = null;
        countdownEl.textContent = '';
    }

    function startAuto() {
        stopAuto();
        remaining = 30;
        countdownEl.textContent = `(in ${remaining}s)`;
        timer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                window.location.reload();
                return;
            }
            countdownEl.textContent = `(in ${remaining}s)`;
        }, 1000);
    }

    function applyAuto() {
        renderToggle();
        if (enabled) startAuto();
        else stopAuto();
    }

    toggleBtn?.addEventListener('click', () => {
        enabled = !enabled;
        localStorage.setItem(KEY, enabled ? '1' : '0');
        applyAuto();
        toast(enabled ? 'Auto refresh ON' : 'Auto refresh OFF');
    });

    applyAuto();
});
</script>

</body>
</html>
