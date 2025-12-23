<?php

namespace App\Livewire\Auditor;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use App\Models\UnifiedLog;
use Carbon\Carbon;

#[Layout('components.layouts.auditor')]
#[Title('Auditor Dashboard')]
class Dashboard extends Component
{
    public array $cards = [];
    public array $chartLabels = [];
    public array $chartValues = [];
    public $latestLogs = [];

    public function mount(): void
    {
        // Cards
        $totalLogs = UnifiedLog::query()->count();

        $todayLogs = UnifiedLog::query()
            ->whereDate('created_at', Carbon::today())
            ->count();

        $errorLogs = UnifiedLog::query()
            ->where(function ($q) {
                $q->where('log_type', 'like', '%error%')
                  ->orWhereRaw("CAST(payload AS CHAR) LIKE ?", ['%"error"%'])
                  ->orWhereRaw("CAST(payload AS CHAR) LIKE ?", ['%"exception"%']);
            })
            ->count();

        $this->cards = [
            [
                'label' => 'Total Logs',
                'value' => $totalLogs,
                'icon'  => 'fa-solid fa-database',
            ],
            [
                'label' => 'Logs Hari Ini',
                'value' => $todayLogs,
                'icon'  => 'fa-solid fa-calendar-day',
            ],
            [
                'label' => 'Error Logs',
                'value' => $errorLogs,
                'icon'  => 'fa-solid fa-triangle-exclamation',
            ],
        ];

        // Chart 7 hari terakhir (logs per day)
        $days = collect(range(6, 0))->map(fn($i) => Carbon::today()->subDays($i));

        $counts = UnifiedLog::query()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('created_at', '>=', Carbon::today()->subDays(6)->startOfDay())
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd');

        $this->chartLabels = $days->map(fn($d) => $d->format('d M'))->toArray();
        $this->chartValues = $days->map(function ($d) use ($counts) {
            $key = $d->format('Y-m-d');
            return (int) ($counts[$key] ?? 0);
        })->toArray();

        // 5 log terbaru
        $this->latestLogs = UnifiedLog::query()
            ->with('application')
            ->latest('created_at')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.auditor.dashboard', [
            'cards' => $this->cards,
            'chartLabels' => $this->chartLabels,
            'chartValues' => $this->chartValues,
            'latestLogs' => $this->latestLogs,
        ]);
    }
}
