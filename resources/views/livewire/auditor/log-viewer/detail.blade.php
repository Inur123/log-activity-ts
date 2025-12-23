<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-xs text-slate-500">Auditor • Log Viewer</div>
            <h1 class="text-xs font-bold text-slate-900">Log Detail #{{ $log->id }}</h1>
            <p class="text-sm text-slate-600">
                {{ $log->application->name ?? '-' }} •
                <span class="font-semibold">{{ $log->log_type }}</span> •
                {{ optional($log->created_at)->format('Y-m-d H:i:s') }}
            </p>
        </div>

        <button type="button" wire:click="back"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 cursor-pointer">
            <i class="fa-solid fa-arrow-left"></i> Back
        </button>
    </div>

    {{-- Meta --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500">Application</div>
                <div class="font-semibold text-slate-900">{{ $log->application->name ?? '-' }}</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500">Log Type (API)</div>
                <div class="font-semibold text-slate-900">{{ $log->log_type ?? '-' }}</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500">IP Address</div>
                <div class="font-semibold text-slate-900">{{ $log->ip_address ?? '-' }}</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-xs text-slate-500">User Agent</div>
                <div class="text-sm text-slate-900 break-words">{{ $log->user_agent ?? '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Summary chips --}}
    @if (!empty($summary))
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="font-semibold text-slate-900 mb-3">Summary</div>
            <div class="flex flex-wrap gap-2">
                @foreach ($summary as $k => $v)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700">
                        <span class="font-semibold">{{ $k }}:</span>
                        <span class="max-w-[520px] truncate">{{ is_scalar($v) ? $v : json_encode($v) }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Payload --}}
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div class="font-semibold text-slate-900">Payload</div>
            <div class="text-xs text-slate-500">Readable view</div>
        </div>

        <div class="p-4 sm:p-6">
            @php
                $isAssoc = function (array $arr) {
                    if ($arr === []) return false;
                    return array_keys($arr) !== range(0, count($arr) - 1);
                };

                $renderScalar = function ($value) {
                    if (is_bool($value)) return $value ? 'true' : 'false';
                    if ($value === null) return 'null';
                    if (is_scalar($value)) return (string) $value;
                    return null;
                };

                $maskIfSensitive = function (string $key, ?string $scalar) {
                    $k = strtolower($key);
                    $sensitive = in_array($k, [
                        'password','token','access_token','refresh_token',
                        'authorization','secret','api_key','apikey',
                    ]);
                    return ($sensitive && $scalar !== null) ? '••••••••' : $scalar;
                };

                $renderNode = function ($data, $level = 0) use (&$renderNode, $isAssoc, $renderScalar, $maskIfSensitive) {
                    if (!is_array($data)) $data = ['_value' => $data];

                    echo '<div class="space-y-2">';

                    foreach ($data as $key => $value) {
                        $scalar = $maskIfSensitive((string) $key, $renderScalar($value));

                        $isArray = is_array($value);
                        $hasChildren = $isArray && count($value) > 0;
                        $assoc = $isArray ? $isAssoc($value) : false;

                        echo '<div class="rounded-xl border border-slate-200 bg-white overflow-hidden">';
                        echo '<div class="px-3 py-2 flex items-start justify-between gap-3">';
                        echo '<div class="min-w-0">';
                        echo '<div class="text-xs text-slate-500">Key</div>';
                        echo '<div class="font-semibold text-slate-900 break-words">' . e($key) . '</div>';
                        echo '</div>';

                        echo '<div class="min-w-0 text-right">';
                        echo '<div class="text-xs text-slate-500">Value</div>';

                        if ($scalar !== null) {
                            echo '<div class="text-sm text-slate-800 break-words max-w-[720px]">' . e($scalar) . '</div>';
                        } elseif ($hasChildren) {
                            $label = $assoc ? 'Object' : 'List';
                            echo '<div class="text-xs text-slate-500">' . $label . ' • ' . count($value) . ' item(s)</div>';
                        } else {
                            echo '<div class="text-sm text-slate-800">-</div>';
                        }

                        echo '</div>';
                        echo '</div>';

                        if ($hasChildren) {
                            echo '<div class="border-t border-slate-200 p-3 bg-slate-50">';
                            $renderNode($value, $level + 1);
                            echo '</div>';
                        }

                        echo '</div>';
                    }

                    echo '</div>';
                };
            @endphp

            @if (isset($payload['_raw']))
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Payload bukan JSON valid, tampilkan sebagai teks:
                    <div class="mt-2 font-mono text-xs whitespace-pre-wrap">{{ $payload['_raw'] }}</div>
                </div>
            @else
                @php $renderNode($payload); @endphp
            @endif

            <details class="mt-4 rounded-xl border border-slate-200 bg-white overflow-hidden">
                <summary class="cursor-pointer px-4 sm:px-6 py-4 border-b border-slate-200 font-semibold text-slate-900">
                    Raw JSON (advanced)
                </summary>
                <div class="p-4 sm:p-6">
                    <pre class="p-4 rounded-xl bg-slate-900 text-slate-100 text-xs overflow-x-auto whitespace-pre-wrap">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </details>
        </div>
    </div>
</div>
