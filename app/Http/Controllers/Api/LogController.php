<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use App\Jobs\ProcessUnifiedLog;

class LogController extends Controller
{
    /**
     * SATU ENDPOINT UNTUK SEMUA LOG
     * POST /api/v1/logs
     */
    public function store(Request $request)
    {
        Log::info('API KEY:', [$request->header('X-API-Key')]);
        // Rate Limiting: 1000 requests per minute per API Key
        $key = 'api:' . $request->input('application')->id;

        if (RateLimiter::tooManyAttempts($key, 1000)) {
            return response()->json([
                'success' => false,
                'message' => 'Too Many Requests',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        RateLimiter::hit($key, 60);

        // Validasi MINIMAL
        $validator = Validator::make($request->all(), [
            'log_type' => 'required|in:activity,audit_trail,security,system,custom',
            'payload' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $application = $request->input('application');

            // Data untuk queue
            $logData = [
                'application_id' => $application->id,
                'log_type' => $request->log_type,
                'payload' => $request->payload,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // MASUKKAN KE QUEUE (Async - TIDAK blocking)
            ProcessUnifiedLog::dispatch($logData)->onQueue('logs');

            Log::info('Log queued', [
                'app' => $application->name,
                'type' => $request->log_type
            ]);

            // Response CEPAT! (202 Accepted = processing async)
            return response()->json([
                'success' => true,
                'message' => 'Log received and queued for processing',
                'queued_at' => now()->toDateTimeString()
            ], 202);
        } catch (\Exception $e) {
            Log::error('Failed to queue log', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process log request',
                'error_debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify API Key (Optional - untuk testing)
     * POST /api/v1/auth/verify
     */
    public function verify(Request $request)
    {
        $application = $request->input('application');

        return response()->json([
            'success' => true,
            'message' => 'API Key is valid',
            'application' => [
                'id' => $application->id,
                'name' => $application->name,
                'domain' => $application->domain,
                'stack' => $application->stack
            ]
        ]);
    }
    public function logsView()
    {

        return view('logs.view');
    }
}
