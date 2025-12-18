{{-- resources/views/logs/simple-view.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Log Viewer - Development</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .log-item:hover { background-color: #f8fafc; }
        pre {
            max-height: 200px;
            overflow-y: auto;
            font-size: 12px;
        }
        .badge-activity { background: #3b82f6; color: white; }
        .badge-audit { background: #10b981; color: white; }
        .badge-security { background: #ef4444; color: white; }
        .badge-system { background: #8b5cf6; color: white; }
        .badge-custom { background: #f59e0b; color: white; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 bg-white rounded-lg shadow p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-database mr-2"></i>Log System Viewer
            </h1>
            <p class="text-gray-600 mb-4">Development Mode - No Authentication Required</p>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-700">
                        {{ \App\Models\UnifiedLog::count() }}
                    </div>
                    <div class="text-sm text-blue-600">Total Logs</div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-700">
                        {{ \App\Models\Application::count() }}
                    </div>
                    <div class="text-sm text-green-600">Applications</div>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-700">
                        {{ \App\Models\UnifiedLog::whereDate('created_at', today())->count() }}
                    </div>
                    <div class="text-sm text-purple-600">Today's Logs</div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-700">
                        {{ \App\Models\UnifiedLog::where('log_type', 'activity')->count() }}
                    </div>
                    <div class="text-sm text-yellow-600">Activity Logs</div>
                </div>
            </div>
        </div>



        <!-- Logs Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">Recent Logs</h2>
                <p class="text-sm text-gray-600">Showing {{ $logs->count() }} of {{ $logs->total() }} logs</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Application</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payload</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hash</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($logs as $log)
                        <tr class="log-item hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                #{{ $log->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-cube text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $log->application->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $log->application->stack }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badgeClasses = [
                                        'activity' => 'badge-activity',
                                        'audit_trail' => 'badge-audit',
                                        'security' => 'badge-security',
                                        'system' => 'badge-system',
                                        'custom' => 'badge-custom'
                                    ];
                                @endphp
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeClasses[$log->log_type] ?? 'badge-custom' }}">
                                    <i class="fas fa-{{ $log->log_type == 'activity' ? 'user' : ($log->log_type == 'audit_trail' ? 'history' : ($log->log_type == 'security' ? 'shield-alt' : 'cog')) }} mr-1"></i>
                                    {{ str_replace('_', ' ', $log->log_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 font-medium mb-1">
                                    @if(isset($log->payload['event']))
                                        {{ $log->payload['event'] }}
                                    @elseif(isset($log->payload['action']))
                                        {{ $log->payload['action'] }}
                                    @else
                                        Custom Data
                                    @endif
                                </div>
                                <pre class="text-xs text-gray-600 bg-gray-100 p-2 rounded mt-1">{{ json_encode($log->payload, JSON_PRETTY_PRINT) }}</pre>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $log->created_at->format('Y-m-d') }}</div>
                                <div>{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500" title="{{ $log->hash }}">
                                {{ substr($log->hash, 0, 10) }}...
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No logs yet. Send some API requests!</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($logs->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>

    <script>
        // Simple filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const appSelect = document.querySelector('select:nth-of-type(1)');
            const typeSelect = document.querySelector('select:nth-of-type(2)');
            const filterBtn = document.querySelector('button.bg-blue-600');

            filterBtn.addEventListener('click', function() {
                const appId = appSelect.value;
                const logType = typeSelect.value;
                let url = '/logs/view?';

                if (appId) url += `application_id=${appId}&`;
                if (logType) url += `log_type=${logType}`;

                window.location.href = url;
            });

            // Auto-refresh every 30 seconds
            setInterval(() => {
                window.location.reload();
            }, 30000);
        });
    </script>
</body>
</html>
