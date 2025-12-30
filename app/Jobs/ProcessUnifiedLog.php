<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\UnifiedLog;
use App\Services\HashChainService;
use Illuminate\Support\Facades\Log;

class ProcessUnifiedLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1, 2, 5 minutes

    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        try {
            $hashService = new HashChainService();

            //  prev hash per application_id
            $previousHash = $hashService->getPreviousHash($this->data['application_id']);

            //  created_at harus konsisten (masuk hash & disimpan)
            $createdAt = now()->toISOString();

            //  data yang dipakai untuk hashing harus sama dengan verifyChain()
            $dataForHash = [
                'application_id' => (string) $this->data['application_id'],
                'log_type'       => (string) $this->data['log_type'],
                'payload'        => $this->data['payload'],
                'ip_address'     => $this->data['ip_address'] ?? null,
                'user_agent'     => $this->data['user_agent'] ?? null,
                'created_at'     => $createdAt,
            ];

            $hash = $hashService->generateHash($dataForHash, $previousHash);

            //  save immutable log (created_at harus sama persis)
            UnifiedLog::create([
                ...$this->data,
                'hash'      => $hash,
                'prev_hash' => $previousHash,
                'created_at'=> $createdAt,
            ]);

            Log::info('Log processed successfully', [
                'application_id' => $this->data['application_id'],
                'log_type'       => $this->data['log_type'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process log', [
                'data'  => $this->data,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Untuk retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessUnifiedLog job failed permanently', [
            'data'  => $this->data,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
