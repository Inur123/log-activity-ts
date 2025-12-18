<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LogController;
use App\Models\UnifiedLog;
use App\Models\Application;
use Illuminate\Http\Request;

// Welcome page
Route::get('/', function () {
    return view('welcome');
});

// Dashboard (butuh login)
// Route::middleware(['auth'])->group(function () {
//     Route::get('/dashboard', function () {
//         // Manual check role
//         if (auth()->user()->role !== 'super_admin') {
//             abort(403);
//         }
//         return view('dashboard');
//     });
// });

// Development routes (tanpa auth)
Route::get('/test/api-keys', function () {
    return response()->json([
        'message' => 'Development mode',
        'api_endpoint' => 'POST /api/v1/logs'
    ]);
});


Route::get('/logs/view', function (Request $request) {
    $query = UnifiedLog::with('application')
        ->orderBy('created_at', 'desc');

    $logs = $query->paginate(50);
    $applications = Application::all();

    return view('logs.simple-view', compact('logs', 'applications'));
});

Route::get('/logs/json', function () {
    $logs = UnifiedLog::with('application')
        ->orderBy('created_at', 'desc')
        ->limit(100)
        ->get()
        ->map(function ($log) {
            return [
                'id' => $log->id,
                'application' => $log->application->name,
                'log_type' => $log->log_type,
                'payload' => $log->payload,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'hash' => substr($log->hash, 0, 10) . '...'
            ];
        });

    return response()->json([
        'total' => UnifiedLog::count(),
        'logs' => $logs
    ]);
});
