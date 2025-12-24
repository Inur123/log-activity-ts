<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessUnifiedLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LogController extends Controller
{
    public function store(Request $request)
    {
        // application bisa dari middleware (attributes) atau input
        $application = $request->input('application') ?? $request->attributes->get('application');

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid application context',
            ], 401);
        }

        // Rate limit per application_id
        $key = 'api:' . $application->id;

        if (RateLimiter::tooManyAttempts($key, 1000)) {
            return response()->json([
                'success' => false,
                'message' => 'Too Many Requests',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // ✅ Validasi: log_type + payload harus sesuai schema log API kamu
        $validator = Validator::make($request->all(), [
            'log_type' => ['required', 'string', 'max:100'],
            'payload'  => ['required', 'array'],

            // schema minimal payload (wajib)
            'payload.event'    => ['required', 'string', Rule::in(['created','updated','deleted','restored','force_deleted'])],
            'payload.model'    => ['required', 'string', 'max:150'],
            'payload.table'    => ['required', 'string', 'max:150'],
            'payload.model_id' => ['required', 'string', 'max:100'],

            // optional tapi kalau ada harus benar
            'payload.actor'          => ['nullable', 'array'],
            'payload.actor.user_id'  => ['nullable', 'string', 'max:100'],
            'payload.request'        => ['nullable', 'array'],
            'payload.request.method' => ['nullable', 'string', 'max:10'],
            'payload.request.url'    => ['nullable', 'string', 'max:2000'],

            // timestamp (boleh salah satu)
            'payload.occurred_at_iso' => ['nullable', 'date'],
            'payload.occurred_at_wib' => ['nullable', 'string', 'max:100'],
        ], [
            'payload.event.in' => 'payload.event tidak valid',
        ]);

        // ✅ Validasi tambahan: event pada log_type harus sama dengan payload.event
        $validator->after(function ($validator) use ($request) {
            $logType = (string) $request->input('log_type');
            $payload = (array) $request->input('payload', []);

            $payloadEvent = data_get($payload, 'event');

            // ambil event dari log_type (contoh: model.surat.created -> created)
            $parts = explode('.', $logType);
            $eventFromType = end($parts);

            $validEvents = ['created','updated','deleted','restored','force_deleted'];

            if ($payloadEvent && in_array($eventFromType, $validEvents, true) && $eventFromType !== $payloadEvent) {
                $validator->errors()->add('payload.event', 'payload.event harus sama dengan event pada log_type');
            }

            // minimal salah satu timestamp ada
            if (!data_get($payload, 'occurred_at_iso') && !data_get($payload, 'occurred_at_wib')) {
                $validator->errors()->add('payload.occurred_at', 'payload wajib memiliki occurred_at_iso atau occurred_at_wib');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $logData = [
                'application_id' => $application->id,
                'log_type'       => $request->string('log_type')->toString(),
                'payload'        => $request->input('payload'),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ];

            ProcessUnifiedLog::dispatch($logData)->onQueue('logs');

            return response()->json([
                'success' => true,
                'message' => 'Log received and queued for processing',
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

    public function verify(Request $request)
    {
        $application = $request->input('application') ?? $request->attributes->get('application');

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid application context',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'API Key is valid',
            'application' => [
                'id' => $application->id,
                'name' => $application->name,
                'domain' => $application->domain,
                'stack' => $application->stack,
            ],
        ]);
    }
}
