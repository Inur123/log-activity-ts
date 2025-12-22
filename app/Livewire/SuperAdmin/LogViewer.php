<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Application;
use App\Models\UnifiedLog;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.super-admin')]
#[Title('Log Viewer')]
class LogViewer extends Component
{
    public string $action = 'index';

    // ✅ UUID string
    public ?string $logId = null;
    public ?UnifiedLog $selectedLog = null;

    public string $q = '';
    public ?int $application_id = null;
    public string $log_type = '';
    public string $from = '';
    public string $to = '';
    public int $per_page = 25;
    public string $sort = 'newest';

    public int $page = 1;

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

    // ✅ UUID param
    public function showDetail(string $id): void
    {
        $this->logId = $id;
        $this->selectedLog = UnifiedLog::with('application')->findOrFail($id);
        $this->action = 'detail';
    }

    public function back(): void
    {
        $this->action = 'index';
        $this->logId = null;
        $this->selectedLog = null;
    }

    private function buildQuery()
    {
        $query = UnifiedLog::query()->with('application');

        $this->sort === 'oldest'
            ? $query->oldest('created_at')
            : $query->latest('created_at');

        if ($this->application_id) $query->where('application_id', $this->application_id);
        if ($this->log_type !== '') $query->where('log_type', $this->log_type);
        if ($this->from) $query->whereDate('created_at', '>=', $this->from);
        if ($this->to) $query->whereDate('created_at', '<=', $this->to);

        if ($this->q !== '') {
            $q = trim($this->q);

            $query->where(function ($sub) use ($q) {
                // UUID bukan digit-only, jadi bagian ini opsional:
                // if (ctype_digit($q)) { ... }

                $sub->orWhere('id', $q) // ✅ search langsung UUID
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
                ]);
            })(),

            default => view('livewire.super-admin.log-viewer.index', [
                'logs' => $this->getFilteredLogs()[0],
                'total' => $this->getFilteredLogs()[1],
                'lastPage' => $this->getFilteredLogs()[2],
                'applications' => Application::orderBy('name')->get(),
                'logTypeOptions' => UnifiedLog::query()
                    ->whereNotNull('log_type')
                    ->where('log_type', '!=', '')
                    ->distinct()
                    ->orderBy('log_type')
                    ->pluck('log_type'),
                'page' => $this->page,
            ]),
        };
    }
}
