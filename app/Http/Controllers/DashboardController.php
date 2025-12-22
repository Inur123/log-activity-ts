<?php

namespace App\Http\Controllers;

use App\Models\UnifiedLog;
use App\Models\Application;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ==== VALIDATION (aman & rapi) ====
        $request->validate([
            'q'              => ['nullable', 'string', 'max:255'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],

            // ✅ tambah authentication (dan tetap support security lama)
            'log_type'       => ['nullable', 'in:activity,audit_trail,authentication,security,system,custom'],

            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date', 'after_or_equal:from'],
            'per_page'       => ['nullable', 'integer', 'in:10,25,50,100'],
            'sort'           => ['nullable', 'in:newest,oldest'],
        ]);

        // ==== INPUT ====
        $q             = trim((string) $request->q);
        $applicationId = $request->application_id;
        $logType       = $request->log_type;
        $from          = $request->from;
        $to            = $request->to;
        $perPage       = $request->per_page ?? 25;
        $sort          = $request->sort ?? 'newest';

        // ==== BASE QUERY ====
        $query = UnifiedLog::with('application');

        // ==== SORT ====
        $sort === 'oldest'
            ? $query->oldest('created_at')
            : $query->latest('created_at');

        // ==== FILTER APPLICATION ====
        if ($applicationId) {
            $query->where('application_id', $applicationId);
        }

        // ==== FILTER TYPE ====
        if ($logType) {
            // ✅ jika pilih authentication, ambil juga data lama security
            if ($logType === 'authentication') {
                $query->whereIn('log_type', ['authentication', 'security']);
            } else {
                $query->where('log_type', $logType);
            }
        }

        // ==== FILTER DATE RANGE ====
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        // ==== SEARCH (ID / Payload / App Name) ====
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                if (ctype_digit($q)) {
                    $sub->orWhere('id', (int) $q);
                }

                $sub->orWhere('payload', 'like', "%{$q}%")
                    ->orWhereHas('application', function ($app) use ($q) {
                        $app->where('name', 'like', "%{$q}%");
                    });
            });
        }

        // ==== PAGINATION + QUERY STRING ====
        $logs = $query->paginate($perPage)->withQueryString();

        // ==== SUPPORT DATA ====
        $applications = Application::orderBy('name')->get();

        return view('dashboard.index', compact('logs', 'applications'));
    }
}
