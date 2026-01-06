<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Application;
use App\Models\UnifiedLog;
use App\Services\HashChainService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.super-admin')]
#[Title('Log Viewer')]
class LogViewer extends Component
{
    public string $action = 'index';

    // UUID string
    public ?string $logId = null;
    public ?UnifiedLog $selectedLog = null;

    public string $q = '';

    /**
     * FIX: string agar aman untuk UUID / numeric.
     * '' = All
     */
    public string $application_id = '';

    public string $log_type = '';
    public string $from = '';
    public string $to = '';
    public int $per_page = 25;
    public string $sort = 'newest';

    public int $page = 1;

    // ✅ SECURITY STATUS
    public ?array $chainStatus = null;
    public ?array $logSecurityStatus = null;
    public bool $verifying = false;

    /**
     * Reset page saat filter berubah
     */
    public function updated($name, $value): void
    {
        if (in_array($name, [
            'q', 'application_id', 'log_type', 'from', 'to', 'per_page', 'sort'
        ], true)) {
            $this->page = 1;
        }
    }

    public function gotoPage(int $p, int $lastPage): void
    {
        $this->page = max(1, min($p, $lastPage));
    }

    public function nextPage(int $lastPage): void
    {
        if ($this->page < $lastPage) $this->page++;
    }

    public function prevPage(): void
    {
        if ($this->page > 1) $this->page--;
    }

    /**
     * ✅ Verify Chain per Application
     */
    public function verifySelectedApplicationChain(): void
    {
        $this->chainStatus = null;

        if ($this->application_id === '') {
            $this->chainStatus = [
                'valid' => false,
                'message' => 'Pilih Application dulu untuk verifikasi chain.',
                'errors' => [],
                'total_checked' => 0,
            ];
            return;
        }

        $this->verifying = true;

        try {
            $service = new HashChainService();
            $this->chainStatus = $service->verifyChainByApplication($this->application_id);
        } catch (\Throwable $e) {
            $this->chainStatus = [
                'valid' => false,
                'message' => 'Verify failed: ' . $e->getMessage(),
                'errors' => [],
                'total_checked' => 0,
            ];
        } finally {
            $this->verifying = false;
        }
    }

    /**
     * ✅ Show Detail + Verify log security
     */
    public function showDetail(string $id): void
    {
        $this->logId = $id;
        $this->selectedLog = UnifiedLog::with('application')->findOrFail($id);

        try {
            $service = new HashChainService();
            $this->logSecurityStatus = $service->verifySingleLog($this->selectedLog);
        } catch (\Throwable $e) {
            $this->logSecurityStatus = [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }

        $this->action = 'detail';
    }

    public function back(): void
    {
        $this->action = 'index';
        $this->logId = null;
        $this->selectedLog = null;
        $this->logSecurityStatus = null;
    }
    public function clearChainStatus(): void
{
    $this->chainStatus = null;
}

    private function buildQuery()
    {
        $query = UnifiedLog::query()->with('application');

        $this->sort === 'oldest'
            ? $query->oldest('created_at')
            : $query->latest('created_at');

        // ✅ Filter Application
        if ($this->application_id !== '') {
            $query->where('application_id', $this->application_id);
        }

        if ($this->log_type !== '') $query->where('log_type', $this->log_type);
        if ($this->from) $query->whereDate('created_at', '>=', $this->from);
        if ($this->to) $query->whereDate('created_at', '<=', $this->to);

        if ($this->q !== '') {
            $q = trim($this->q);

            $query->where(function ($sub) use ($q) {
                $sub->orWhere('id', $q)
                    ->orWhereRaw("CAST(payload AS CHAR) LIKE ?", ["%$q%"])
                    ->orWhereHas('application', fn($app) =>
                        $app->where('name', 'like', "%$q%")
                    );
            });
        }

        return $query;
    }

    public function getFilteredLogs()
    {
        $base = $this->buildQuery();

        $total = (clone $base)->count();
        $perPage = $this->per_page;

        $lastPage = max(1, (int) ceil($total / $perPage));

        if ($this->page > $lastPage) $this->page = $lastPage;

        $items = $base->forPage($this->page, $perPage)->get();

        return [$items, $total, $lastPage];
    }

    private function payloadToArray(mixed $payload): array
    {
        if (is_array($payload)) return $payload;

        if (is_object($payload)) {
            $arr = json_decode(json_encode($payload), true);
            return is_array($arr) ? $arr : ['_raw' => json_encode($payload)];
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
            return ['_raw' => $payload];
        }

        if ($payload === null) return [];
        return ['_raw' => (string) $payload];
    }

    private function buildSummary(array $data): array
    {
        $pick = function (array $keys) use ($data) {
            foreach ($keys as $k) {
                $v = data_get($data, $k);
                if ($v !== null && $v !== '' && $v !== []) return $v;
            }
            return null;
        };

        $summary = [
            'Status' => $pick(['status', 'code', 'http.status', 'response.status']),
            'Method' => $pick(['method', 'http.method', 'request.method']),
            'URL'    => $pick(['url', 'path', 'endpoint', 'http.url', 'request.url']),
            'User'   => $pick(['user.email', 'user.name', 'user_id', 'auth.user_id']),
            'Action' => $pick(['action', 'event', 'type', 'message']),
            'Error'  => $pick(['error.message', 'error', 'exception.message', 'exception']),
        ];

        return array_filter($summary, fn($v) => $v !== null);
    }

    public function render()
    {
        return match ($this->action) {
            'detail' => (function () {
                $payloadArr = $this->payloadToArray($this->selectedLog?->payload);

                return view('livewire.super-admin.log-viewer.detail', [
                    'log' => $this->selectedLog,
                    'payload' => $payloadArr,
                    'summary' => $this->buildSummary($payloadArr),
                    'logSecurityStatus' => $this->logSecurityStatus,
                ]);
            })(),

            default => (function () {
                [$logs, $total, $lastPage] = $this->getFilteredLogs();

                return view('livewire.super-admin.log-viewer.index', [
                    'logs' => $logs,
                    'total' => $total,
                    'lastPage' => $lastPage,
                    'applications' => Application::orderBy('name')->get(),
                    'logTypeOptions' => UnifiedLog::query()
                        ->whereNotNull('log_type')
                        ->where('log_type', '!=', '')
                        ->distinct()
                        ->orderBy('log_type')
                        ->pluck('log_type'),
                    'page' => $this->page,
                    'per_page' => $this->per_page,
                    'chainStatus' => $this->chainStatus,
                    'verifying' => $this->verifying,
                ]);
            })(),
        };
    }
}
