<?php

namespace App\Http\Controllers;

use App\Models\UnifiedLog;
use App\Models\Application;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q'              => ['nullable', 'string', 'max:255'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'log_type'       => ['nullable', 'string', 'max:100'],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date', 'after_or_equal:from'],
            'per_page'       => ['nullable', 'integer', 'in:10,25,50,100'],
            'sort'           => ['nullable', 'in:newest,oldest'],
        ]);

        $q             = trim((string) $request->q);
        $applicationId = $request->application_id;
        $logType       = $request->log_type;
        $from          = $request->from;
        $to            = $request->to;
        $perPage       = (int) ($request->per_page ?? 25);
        $sort          = $request->sort ?? 'newest';

        $query = UnifiedLog::with('application');

        if ($sort === 'oldest') $query->oldest('created_at');
        else $query->latest('created_at');

        if (!empty($applicationId)) {
            $query->where('application_id', $applicationId);
        }

        // ✅ filter sesuai value mentah dari dropdown (log_type di DB)
        if (!empty($logType)) {
            $query->where('log_type', $logType);
        }

        if (!empty($from)) $query->whereDate('created_at', '>=', $from);
        if (!empty($to))   $query->whereDate('created_at', '<=', $to);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                if (ctype_digit($q)) {
                    $sub->orWhere('id', (int) $q);
                }

                // NOTE: kalau payload tipe JSON, ini bisa kamu ganti CAST(payload AS CHAR)
                $sub->orWhere('payload', 'like', "%{$q}%")
                    ->orWhereHas('application', function ($app) use ($q) {
                        $app->where('name', 'like', "%{$q}%");
                    });
            });
        }

        $logs = $query->paginate($perPage)->withQueryString();

        $applications = Application::orderBy('name')->get();

        // ✅ options = log_type mentah, tanpa label map sama sekali
        $logTypeOptions = UnifiedLog::query()
            ->whereNotNull('log_type')
            ->where('log_type', '!=', '')
            ->distinct()
            ->orderBy('log_type')
            ->pluck('log_type')
            ->values()
            ->all();

        return view('dashboard.index', compact('logs', 'applications', 'logTypeOptions'));
    }
}
