<?php

namespace App\Jobs;

use App\Models\UnifiedLog;
use App\Services\HashChainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUnifiedLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [60, 120, 300];

    public function __construct(protected array $data) {}

    public function handle(): void
    {
        try {
            $hashService = new HashChainService();

            // ✅ pakai created_at yang FIX (dipakai untuk hash & disimpan)
            $createdAt = now();

            // ✅ prev_hash harus per application (biar chain tidak campur antar app)
            $previousHash = $hashService->getPreviousHash($this->data['application_id']);

            // ✅ data yang di-hash HARUS sama dengan yang nanti di-verify
            $dataForHash = [
                'application_id' => $this->data['application_id'],
                'log_type'       => $this->data['log_type'],
                'payload'        => $this->data['payload'],
                'ip_address'     => $this->data['ip_address'] ?? null,
                'user_agent'     => $this->data['user_agent'] ?? null,
                'created_at'     => $createdAt->toISOString(),
            ];

            $hash = $hashService->generateHash($dataForHash, $previousHash);

            UnifiedLog::create([
                ...$this->data,
                'hash' => $hash,
                'prev_hash' => $previousHash,
                'created_at' => $createdAt, // ✅ simpan created_at sama persis
            ]);

            Log::info('[WEBLOG] Log processed', [
                'application_id' => $this->data['application_id'],
                'log_type' => $this->data['log_type'],
            ]);

        } catch (\Throwable $e) {
            Log::error('[WEBLOG] Failed to process log', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('[WEBLOG] ProcessUnifiedLog failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}
