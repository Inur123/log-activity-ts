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

        /**
         *  Rate limit per application_id (maks 1000/minute)
         */
        $key = 'api:' . $application->id;

        if (RateLimiter::tooManyAttempts($key, 1000)) {
            return response()->json([
                'success'     => false,
                'message'     => 'Too Many Requests',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, 60);

        /**
         *  Decode raw body kalau request->all() kosong
         */
        $rawBody = [];
        if ($request->getContent()) {
            $decoded = json_decode($request->getContent(), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $rawBody = $decoded;

                if (empty($request->all())) {
                    $request->merge($rawBody);
                }
            }
        }

        /**
         *  Ambil log_type (fallback root -> raw -> payload.log_type)
         */
        $rawLogType = $request->input('log_type')
            ?? ($rawBody['log_type'] ?? null)
            ?? data_get($request->input('payload', []), 'log_type')
            ?? ($rawBody['payload']['log_type'] ?? null);

        $logType = strtoupper(trim((string) $rawLogType));
        if ($logType === '') $logType = 'UNKNOWN';

        $request->merge(['log_type' => $logType]);

        /**
         *  Decode payload kalau string JSON
         */
        $rawPayload = $request->input('payload');

        if (is_string($rawPayload)) {
            $decoded = json_decode($rawPayload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $rawPayload = $decoded;
            } else {
                $rawPayload = ['_raw' => $rawPayload];
            }

            $request->merge(['payload' => $rawPayload]);
        }

        if ($rawPayload === null) {
            $request->merge(['payload' => []]);
        }

        /**
         *  BASIC validation
         */
        $validator = Validator::make($request->all(), [
            'log_type' => 'required|string|in:' . implode(',', $this->allowedLogTypes()),
            'payload'  => 'required|array',
        ]);

        if ($validator->fails()) {
            $this->dispatchValidationFailed(
                application: $application,
                originalLogType: $logType,
                stage: 'BASIC',
                errors: $validator->errors()->toArray(),
                originalPayload: (array) $request->input('payload', [])
            );

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /**
         *  PAYLOAD validation by log_type
         */
        $payloadRules = $this->payloadRulesFor($logType);

        if (! empty($payloadRules)) {
            $payloadValidator = Validator::make($request->input('payload', []), $payloadRules);

            if ($payloadValidator->fails()) {
                $this->dispatchValidationFailed(
                    application: $application,
                    originalLogType: $logType,
                    stage: 'PAYLOAD',
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
            /**
             *  VALID request -> PASSED
             */
            $payload = $request->input('payload', []);
            $payload['validation'] = [
                'status' => 'PASSED',
                'stage'  => null,
                'errors' => null,
            ];

            $logData = [
                'application_id' => $application->id,
                'log_type'       => $logType,
                'payload'        => $payload,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ];

            ProcessUnifiedLog::dispatch($logData)->onQueue('logs');

            return response()->json([
                'success'   => true,
                'message'   => 'Log received and queued for processing',
                'queued_at' => now()->toDateTimeString(),
            ], 202);

        } catch (\Throwable $e) {

            Log::error('Failed to queue log', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process log request',
            ], 500);
        }
    }

    /**
     *  Log invalid request/payload
     */
    private function dispatchValidationFailed($application, string $originalLogType, string $stage, array $errors, array $originalPayload): void
    {
        $logType = strtoupper(trim($originalLogType));
        if ($logType === '') $logType = 'UNKNOWN';

        $rateKey = 'validation_failed:' . $application->id . ':' . md5($logType . $stage . json_encode($errors));

        if (RateLimiter::tooManyAttempts($rateKey, 30)) {
            return;
        }

        RateLimiter::hit($rateKey, 60);

        $payload = array_merge($originalPayload, [
            'validation' => [
                'status' => 'FAILED',
                'stage'  => $stage,
                'errors' => $errors,
            ],
        ]);

        $validationLogData = [
            'application_id' => $application->id,
            'log_type'       => $logType,
            'payload'        => $payload,
            'ip_address'     => request()?->ip(),
            'user_agent'     => request()?->userAgent(),
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
            'SECURITY_VIOLATION',
            'PERMISSION_CHANGE',
        ];
    }

    private function payloadRulesFor(string $logType): array
    {
        return match ($logType) {

            'AUTH_LOGIN',
            'AUTH_LOGOUT',
            'AUTH_LOGIN_FAILED' => [
                'user_id' => 'nullable|integer',
                'email'   => 'required|email',
                'ip'      => 'nullable|string',
                'device'  => 'nullable|string',
            ],

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

            'SYSTEM_ERROR' => [
                'message'   => 'required|string',
                'code'      => 'nullable|string',
                'trace_id'  => 'nullable|string',
                'context'   => 'nullable|array',
            ],

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
