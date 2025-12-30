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

        if (! $application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid application context',
            ], 401);
        }

        // Rate limit per application_id (maks 1000/minute)
        $key = 'api:' . $application->id;

        if (RateLimiter::tooManyAttempts($key, 1000)) {
            return response()->json([
                'success'     => false,
                'message'     => 'Too Many Requests',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, 60);

        // normalize log_type to uppercase
        $logType = strtoupper($request->input('log_type'));

        // validate basic request
        $validator = Validator::make([
            'log_type' => $logType,
            'payload'  => $request->input('payload'),
        ], [
            'log_type' => 'required|string|in:' . implode(',', $this->allowedLogTypes()),
            'payload'  => 'required|array',
        ]);

        if ($validator->fails()) {

            // Simpan VALIDATION_FAILED (basic validation)
            $this->dispatchValidationFailed(
                application: $application,
                originalLogType: $logType ?: 'UNKNOWN',
                errors: $validator->errors()->toArray(),
                originalPayload: (array) $request->input('payload', [])
            );

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // validate payload rules by log_type
        $payloadRules = $this->payloadRulesFor($logType);

        if (! empty($payloadRules)) {
            $payloadValidator = Validator::make($request->input('payload', []), $payloadRules);

            if ($payloadValidator->fails()) {

                // Simpan VALIDATION_FAILED (payload validation)
                $this->dispatchValidationFailed(
                    application: $application,
                    originalLogType: $logType,
                    errors: $payloadValidator->errors()->toArray(),
                    originalPayload: (array) $request->input('payload', [])
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Payload validation failed',
                    'errors'  => $payloadValidator->errors(),
                ], 422);
            }
        }

        try {
            // valid request -> masuk ke log aslinya
            $logData = [
                'application_id' => $application->id,
                'log_type'       => $logType,
                'payload'        => $request->input('payload', []),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ];

            ProcessUnifiedLog::dispatch($logData)->onQueue('logs');

            return response()->json([
                'success'   => true,
                'message'   => 'Log received and queued for processing',
                'queued_at' => now()->toDateTimeString(),
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

        if (! $application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid application context',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'API Key is valid',
            'application' => [
                'id'     => $application->id,
                'name'   => $application->name,
                'domain' => $application->domain,
                'stack'  => $application->stack,
            ],
        ]);
    }

    /**
     * Log invalid request/payload sebagai VALIDATION_FAILED
     */
    private function dispatchValidationFailed($application, string $originalLogType, array $errors, array $originalPayload): void
    {
        // Rate limit per error type supaya tidak spam
        $rateKey = 'validation_failed:' . $application->id . ':' . md5($originalLogType . json_encode($errors));

        if (RateLimiter::tooManyAttempts($rateKey, 30)) {
            return;
        }

        RateLimiter::hit($rateKey, 60);

        $validationLogData = [
            'application_id' => $application->id,
            'log_type'       => 'VALIDATION_FAILED',
            'payload'        => [
                'user_id' => $originalPayload['user_id'] ?? null,
                'errors'  => $errors,
                'ip'      => request()?->ip(),
                'meta'    => [
                    'original_log_type' => $originalLogType,
                    'original_payload'  => $originalPayload,
                ],
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];

        ProcessUnifiedLog::dispatch($validationLogData)->onQueue('logs');
    }

    private function allowedLogTypes(): array
    {
        return [
            'AUTH_LOGIN',
            'AUTH_LOGOUT',
            'AUTH_LOGIN_FAILED',

            'ACCESS_ENDPOINT',
            'DOWNLOAD_DOCUMENT',
            'SEND_EXTERNAL',

            'DATA_CREATE',
            'DATA_UPDATE',
            'DATA_DELETE',
            'STATUS_CHANGE',
            'BULK_IMPORT',
            'BULK_EXPORT',

            'SYSTEM_ERROR',
            'VALIDATION_FAILED',

            'SECURITY_VIOLATION',
            'PERMISSION_CHANGE',
        ];
    }

    private function payloadRulesFor(string $logType): array
    {
        return match ($logType) {

            // === AUTH ===
            'AUTH_LOGIN',
            'AUTH_LOGOUT',
            'AUTH_LOGIN_FAILED' => [
                'user_id' => 'nullable|integer',
                'email'   => 'required|email',
                'ip'      => 'nullable|string',
                'device'  => 'nullable|string',
            ],

            // === ACCESS ===
            'ACCESS_ENDPOINT' => [
                'user_id'   => 'required|integer',
                'endpoint'  => 'required|string',
                'method'    => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
                'ip'        => 'nullable|string',
                'status'    => 'required|integer',
            ],

            'DOWNLOAD_DOCUMENT' => [
                'user_id'        => 'required|integer',
                'document_id'    => 'required',
                'document_name'  => 'nullable|string',
                'ip'             => 'nullable|string',
            ],

            'SEND_EXTERNAL' => [
                'user_id'  => 'required|integer',
                'channel'  => 'required|string|in:WA,EMAIL,API',
                'to'       => 'required|string',
                'message'  => 'nullable|string',
                'meta'     => 'nullable|array',
            ],

            // === DATA ===
            'DATA_CREATE' => [
                'user_id' => 'required|integer',
                'data'    => 'required|array',
            ],

            'DATA_UPDATE' => [
                'user_id' => 'required|integer',
                'before'  => 'required|array',
                'after'   => 'required|array',
            ],

            'DATA_DELETE' => [
                'user_id' => 'required|integer',
                'id'      => 'required',
                'reason'  => 'nullable|string',
            ],

            'STATUS_CHANGE' => [
                'user_id' => 'required|integer',
                'id'      => 'required',
                'from'    => 'required|string',
                'to'      => 'required|string',
            ],

            'BULK_IMPORT',
            'BULK_EXPORT' => [
                'user_id'     => 'required|integer',
                'total_rows'  => 'required|integer|min:1',
                'success'     => 'required|integer|min:0',
                'failed'      => 'required|integer|min:0',
                'file_name'   => 'nullable|string',
            ],

            // === SYSTEM ===
            'SYSTEM_ERROR' => [
                'message'   => 'required|string',
                'code'      => 'nullable|string',
                'trace_id'  => 'nullable|string',
                'context'   => 'nullable|array',
            ],

            'VALIDATION_FAILED' => [
                'user_id' => 'nullable|integer',
                'errors'  => 'required|array',
                'ip'      => 'nullable|string',
                'meta'    => 'nullable|array',
            ],

            // === SECURITY ===
            'SECURITY_VIOLATION' => [
                'user_id' => 'nullable|integer',
                'ip'      => 'nullable|string',
                'reason'  => 'required|string',
                'meta'    => 'nullable|array',
            ],

            'PERMISSION_CHANGE' => [
                'user_id'        => 'required|integer',
                'target_user_id' => 'required|integer',
                'before'         => 'required|array',
                'after'          => 'required|array',
            ],

            default => [],
        };
    }
}
