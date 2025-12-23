<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <div class="text-xs text-slate-500">Super Admin • Dashboard</div>
            <h1 class="text-2xl font-bold text-slate-900">Super Admin Dashboard</h1>
            <p class="text-sm text-slate-600">Ringkasan log, tren 7 hari, dan log terbaru.</p>
        </div>

        <a href="{{ route('super_admin.logs') }}" wire:navigate
            class="inline-flex w-full sm:w-auto items-center justify-center gap-2
              px-4 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800
              whitespace-nowrap">
            <i class="fa-solid fa-database"></i>
            <span>Buka Log Viewer</span>
        </a>
    </div>


    {{-- Cards (4) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($cards as $c)
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-slate-500">{{ $c['label'] }}</div>
                        <div class="mt-1 text-2xl font-bold text-slate-900">
                            {{ number_format($c['value']) }}
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center">
                        <i class="{{ $c['icon'] }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Chart + Latest logs --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

        {{-- Chart --}}
        <div class="lg:col-span-7 rounded-2xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-slate-900">Logs per Hari</div>
                    <div class="text-xs text-slate-500">7 hari terakhir</div>
                </div>
                <div class="text-xs text-slate-500">
                    Updated: {{ now()->format('Y-m-d H:i') }}
                </div>
            </div>
            <div class="p-5">
                <canvas id="logsChart" height="120" data-labels='@json($chartLabels)'
                    data-values='@json($chartValues)'></canvas>
            </div>
        </div>

        {{-- Latest logs --}}
        <div class="lg:col-span-5 rounded-2xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-slate-900">Log Terbaru</div>
                    <div class="text-xs text-slate-500">5 data terakhir</div>
                </div>
                <a href="{{ route('super_admin.logs') }}" wire:navigate
                    class="text-sm font-semibold text-slate-900 hover:underline">
                    Lihat semua
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($latestLogs as $log)
                    @php
                        $payloadPreview = is_string($log->payload)
                            ? $log->payload
                            : json_encode($log->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    @endphp

                    <div class="p-5 hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900 truncate">
                                    {{ $log->application->name ?? '-' }}
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                    <span
                                        class="inline-flex px-2 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-700">
                                        {{ $log->log_type ?: '-' }}
                                    </span>
                                    <span>•</span>
                                    <span>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</span>
                                </div>

                                <div class="mt-2 text-sm text-slate-600 truncate">
                                    {{ $payloadPreview }}
                                </div>
                            </div>

                            <a href="{{ route('super_admin.logs') }}" wire:navigate
                                class="shrink-0 inline-flex items-center justify-center h-9 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm">
                                Detail
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-500">
                        Tidak ada log.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  async function ensureChartJs() {
    if (window.Chart) return;

    // supaya tidak double-load kalau dipanggil berkali-kali
    if (!window.__chartJsLoading) {
      window.__chartJsLoading = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
      });
    }

    await window.__chartJsLoading;
  }

  async function initLogsChart() {
    await ensureChartJs();

    const el = document.getElementById('logsChart');
    if (!el) return;

    if (window.__logsChart) {
      window.__logsChart.destroy();
      window.__logsChart = null;
    }

    let labels = [], values = [];
    try {
      labels = JSON.parse(el.dataset.labels || "[]");
      values = JSON.parse(el.dataset.values || "[]");
    } catch (e) {}

    if (!labels.length) return;

    window.__logsChart = new Chart(el, {
      type: 'line',
      data: { labels, datasets: [{ label: 'Logs', data: values, tension: 0.35 }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  }

  document.addEventListener('DOMContentLoaded', initLogsChart);
  document.addEventListener('livewire:navigated', initLogsChart);

  if (!window.__logsChartObserver) {
    window.__logsChartObserver = new MutationObserver(() => initLogsChart());
    window.__logsChartObserver.observe(document.documentElement, { childList: true, subtree: true });
  }
</script>


