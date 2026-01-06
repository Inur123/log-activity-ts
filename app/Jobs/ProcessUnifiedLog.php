<?php

namespace App\Jobs;

use App\Models\UnifiedLog;
use App\Services\HashChainService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessUnifiedLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [60, 120, 300];

    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        DB::transaction(function () {

            $hashService = new HashChainService();

            $appId = (string) $this->data['application_id'];

            // 🔐 LOCK row terakhir per application
            $lastLog = UnifiedLog::where('application_id', $appId)
                ->orderByDesc('seq')
                ->lockForUpdate()
                ->first();

            $nextSeq = $lastLog ? $lastLog->seq + 1 : 1;
            $prevHash = $lastLog ? $lastLog->hash : str_repeat('0', 64);

            $payload = $this->data['payload'] ?? [];
            $hashService->normalizeArray($payload);

            $hash = $hashService->generateHash(
                applicationId: $appId,
                seq: $nextSeq,
                logType: $this->data['log_type'],
                payload: $payload,
                prevHash: $prevHash
            );

            UnifiedLog::create([
                'application_id' => $appId,
                'seq' => $nextSeq,
                'log_type' => strtoupper($this->data['log_type']),
                'payload' => $payload,
                'ip_address' => $this->data['ip_address'] ?? null,
                'user_agent' => $this->data['user_agent'] ?? null,
                'hash' => $hash,
                'prev_hash' => $prevHash,
            ]);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessUnifiedLog job failed permanently', [
            'data'  => $this->data,
            'error' => $exception->getMessage(),
        ]);
    }
}
