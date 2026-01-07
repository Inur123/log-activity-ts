<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs text-slate-500">Super Admin • Log Viewer</div>

            {{-- ID panjang aman --}}
            <h1 class="text-xs font-bold text-slate-900 break-all">
                Log Detail #<span class="font-mono">{{ $log->id }}</span>
            </h1>

            <p class="text-sm text-slate-600 break-words">
                {{ $log->application->name ?? '-' }} •
                <span class="font-semibold">{{ $log->log_type }}</span> •
              {{ optional($log->created_at)->translatedFormat('l, d F Y') }} | {{ optional($log->created_at)->format('H.i') }} WIB
            </p>
        </div>

        <button type="button" wire:click="back"
            class="shrink-0 inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 cursor-pointer">
            <i class="fa-solid fa-arrow-left"></i> Back
        </button>
    </div>

    {{--  SECURITY STATUS BLOCK --}}
    @if (isset($logSecurityStatus))
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-slate-900 text-white flex items-center justify-center">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <div class="font-semibold text-slate-900">Security Status</div>
                    <div class="text-xs text-slate-500">Validasi hash log ini</div>
                </div>
            </div>

            {{-- Status --}}
            <div class="mt-4">
                @if (($logSecurityStatus['valid'] ?? false) === true)
                    <div
                        class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm flex items-start gap-2">
                        <i class="fa-solid fa-check mt-0.5"></i>
                        <div>Log aman (hash & prev_hash valid)</div>
                    </div>
                @else
                    <div
                        class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm flex items-start gap-2">
                        <i class="fa-solid fa-xmark mt-0.5"></i>
                        <div>Log tidak valid! Data kemungkinan sudah diubah / rusak</div>
                    </div>
                @endif
            </div>

            {{-- Detail info --}}
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-xs text-slate-500">Log UUID</div>
                    <div class="font-mono text-xs text-slate-900 break-all">
                        {{ $logSecurityStatus['log_id'] ?? '-' }}
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-xs text-slate-500">SEQ</div>
                    <div class="font-bold text-slate-900">
                        {{ $logSecurityStatus['seq'] ?? '-' }}
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-xs text-slate-500">Prev Hash Check</div>
                    <div class="text-sm font-semibold flex items-center gap-2">
                        @if (($logSecurityStatus['prev_ok'] ?? false) === true)
                            <i class="fa-solid fa-check text-emerald-600"></i>
                            <span class="text-emerald-700">OK</span>
                        @else
                            <i class="fa-solid fa-xmark text-rose-600"></i>
                            <span class="text-rose-700">MISMATCH</span>
                        @endif
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-xs text-slate-500">Hash Check</div>
                    <div class="text-sm font-semibold flex items-center gap-2">
                        @if (($logSecurityStatus['hash_ok'] ?? false) === true)
                            <i class="fa-solid fa-check text-emerald-600"></i>
                            <span class="text-emerald-700">OK</span>
                        @else
                            <i class="fa-solid fa-xmark text-rose-600"></i>
                            <span class="text-rose-700">MISMATCH</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Hash Detail --}}
            <details class="mt-4 rounded-xl border border-slate-200 bg-white overflow-hidden">
                <summary
                    class="cursor-pointer px-4 sm:px-6 py-4 border-b border-slate-200 font-semibold text-slate-900">
                    Detail Hash (advanced)
                </summary>

                <div class="p-4 sm:p-6 space-y-3">

                    <div>
                        <div class="text-xs text-slate-500 mb-1">Expected Prev Hash</div>
                        <pre class="p-3 rounded-xl bg-slate-900 text-slate-100 text-xs overflow-x-auto break-all whitespace-pre-wrap">{{ $logSecurityStatus['expected_prev_hash'] ?? '-' }}</pre>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500 mb-1">Stored Hash</div>
                        <pre class="p-3 rounded-xl bg-slate-900 text-slate-100 text-xs overflow-x-auto break-all whitespace-pre-wrap">{{ $logSecurityStatus['stored_hash'] ?? '-' }}</pre>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500 mb-1">Recomputed Hash</div>
                        <pre class="p-3 rounded-xl bg-slate-900 text-slate-100 text-xs overflow-x-auto break-all whitespace-pre-wrap">{{ $logSecurityStatus['recomputed_hash'] ?? '-' }}</pre>
                    </div>

                </div>
            </details>
        </div>
    @endif


    {{-- Meta --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 min-w-0">
                <div class="text-xs text-slate-500">Application</div>
                <div class="font-semibold text-slate-900 truncate" title="{{ $log->application->name ?? '-' }}">
                    {{ $log->application->name ?? '-' }}
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 min-w-0">
                <div class="text-xs text-slate-500">Log Type (API)</div>
                <div class="font-semibold text-slate-900 break-all">
                    {{ $log->log_type ?? '-' }}
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 min-w-0">
                <div class="text-xs text-slate-500">IP Address</div>
                <div class="font-semibold text-slate-900 break-all">
                    {{ $log->ip_address ?? '-' }}
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 min-w-0">
                <div class="text-xs text-slate-500">User Agent</div>
                <div class="text-sm text-slate-900 break-all whitespace-normal">
                    {{ $log->user_agent ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Summary chips --}}
    @if (!empty($summary))
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
            <div class="font-semibold text-slate-900 mb-3">Summary</div>
            <div class="flex flex-wrap gap-2">
                @foreach ($summary as $k => $v)
                    @php
                        $sv = is_scalar($v)
                            ? (string) $v
                            : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    @endphp

                    <span
                        class="inline-flex min-w-0 items-center gap-1 px-2 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700">
                        <span class="font-semibold shrink-0">{{ $k }}:</span>
                        <span class="min-w-0 max-w-[12rem] sm:max-w-[520px] truncate" title="{{ $sv }}">
                            {{ $sv }}
                        </span>
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
                    if ($arr === []) {
                        return false;
                    }
                    return array_keys($arr) !== range(0, count($arr) - 1);
                };

                $renderScalar = function ($value) {
                    if (is_bool($value)) {
                        return $value ? 'true' : 'false';
                    }
                    if ($value === null) {
                        return 'null';
                    }
                    if (is_scalar($value)) {
                        return (string) $value;
                    }
                    return null;
                };

                $maskIfSensitive = function (string $key, ?string $scalar) {
                    $k = strtolower($key);
                    $sensitive = in_array($k, [
                        'password',
                        'token',
                        'access_token',
                        'refresh_token',
                        'authorization',
                        'secret',
                        'api_key',
                        'apikey',
                    ]);
                    return $sensitive && $scalar !== null ? '••••••••' : $scalar;
                };

                $renderNode = function ($data, $level = 0) use (
                    &$renderNode,
                    $isAssoc,
                    $renderScalar,
                    $maskIfSensitive,
                ) {
                    if (!is_array($data)) {
                        $data = ['_value' => $data];
                    }

                    echo '<div class="space-y-2">';
                    foreach ($data as $key => $value) {
                        $scalar = $maskIfSensitive((string) $key, $renderScalar($value));
                        $isArray = is_array($value);
                        $hasChildren = $isArray && count($value) > 0;
                        $assoc = $isArray ? $isAssoc($value) : false;

                        echo '<div class="rounded-xl border border-slate-200 bg-white overflow-hidden">';
                        echo '<div class="px-3 py-2 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">';
                        echo '<div class="min-w-0">';
                        echo '<div class="text-xs text-slate-500">Key</div>';
                        echo '<div class="font-semibold text-slate-900 break-all">' . e($key) . '</div>';
                        echo '</div>';

                        echo '<div class="min-w-0 sm:text-right">';
                        echo '<div class="text-xs text-slate-500">Value</div>';

                        if ($scalar !== null) {
                            echo '<div class="text-sm text-slate-800 break-all whitespace-normal max-w-full sm:max-w-[720px]">' .
                                e($scalar) .
                                '</div>';
                        } elseif ($hasChildren) {
                            $label = $assoc ? 'Object' : 'List';
                            echo '<div class="text-xs text-slate-500">' .
                                $label .
                                ' • ' .
                                count($value) .
                                ' item(s)</div>';
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
                    <div class="mt-2 font-mono text-xs whitespace-pre-wrap break-all">{{ $payload['_raw'] }}</div>
                </div>
            @else
                @php $renderNode($payload); @endphp
            @endif

            {{-- Raw JSON --}}
            <details class="mt-4 rounded-xl border border-slate-200 bg-white overflow-hidden">
                <summary
                    class="cursor-pointer px-4 sm:px-6 py-4 border-b border-slate-200 font-semibold text-slate-900">
                    Raw JSON (advanced)
                </summary>
                <div class="p-4 sm:p-6">
                    <pre class="p-4 rounded-xl bg-slate-900 text-slate-100 text-xs overflow-x-auto whitespace-pre-wrap break-all">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </details>
        </div>
    </div>
</div>
