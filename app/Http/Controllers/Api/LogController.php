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
    public function store(Request $request)
    {
        $application = $request->input('application');

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid application context'
            ], 401);
        }

        // Rate limit per application_id
        $key = 'api:' . $application->id;

        if (RateLimiter::tooManyAttempts($key, 1000)) {
            return response()->json([
                'success' => false,
                'message' => 'Too Many Requests',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $validator = Validator::make($request->all(), [
            'log_type' => 'required|string|max:100',
            'payload'  => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $logData = [
                'application_id' => $application->id,
                'log_type'       => $request->log_type,
                'payload'        => $request->payload,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ];

            ProcessUnifiedLog::dispatch($logData)->onQueue('logs');

            return response()->json([
                'success' => true,
                'message' => 'Log received and queued for processing',
                'queued_at' => now()->toDateTimeString()
            ], 202);

        } catch (\Exception $e) {
            Log::error('Failed to queue log', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process log request',
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        $application = $request->input('application');

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid application context'
            ], 401);
        }

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
}
