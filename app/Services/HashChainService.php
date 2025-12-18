<?php

namespace App\Services;

use App\Models\UnifiedLog;
use Illuminate\Support\Facades\DB;

class HashChainService
{
    public function generateHash(array $data, ?string $previousHash = null): string
    {
        // Sort untuk konsistensi
        ksort($data);

        // Gabung dengan previous hash
        $payload = json_encode($data) . ($previousHash ?? '');

        // Generate SHA256
        return hash('sha256', $payload);
    }

    public function getPreviousHash(): ?string
    {
        $lastLog = UnifiedLog::latest('id')->first(['hash']);
        return $lastLog?->hash;
    }

    public function verifyChain(): array
    {
        $logs = UnifiedLog::orderBy('id')->get(['id', 'hash', 'prev_hash', 'created_at']);

        if ($logs->isEmpty()) {
            return ['valid' => true, 'message' => 'No logs to verify'];
        }

        $errors = [];

        foreach ($logs as $index => $log) {
            if ($index === 0) continue;

            $prevLog = $logs[$index - 1];

            if ($log->prev_hash !== $prevLog->hash) {
                $errors[] = [
                    'log_id' => $log->id,
                    'expected' => $prevLog->hash,
                    'found' => $log->prev_hash,
                    'timestamp' => $log->created_at
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Hash chain valid' : 'Hash chain broken',
            'errors' => $errors,
            'total_checked' => $logs->count()
        ];
    }
}
