<?php

namespace App\Services;

use App\Models\UnifiedLog;

class HashChainService
{
    private function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    public function generateHash(array $data, ?string $previousHash = null): string
    {
        $this->ksortRecursive($data);

        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . ($previousHash ?? '');

        return hash('sha256', $payload);
    }

    public function getPreviousHash(?int $applicationId = null): ?string
    {
        $q = UnifiedLog::query()->latest('id');

        if ($applicationId) {
            $q->where('application_id', $applicationId);
        }

        return $q->value('hash');
    }

    public function verifyChain(?int $applicationId = null): array
    {
        $q = UnifiedLog::query()->orderBy('id');

        if ($applicationId) {
            $q->where('application_id', $applicationId);
        }

        $logs = $q->get(['id','application_id','log_type','payload','hash','prev_hash','created_at']);

        if ($logs->isEmpty()) {
            return ['valid' => true, 'message' => 'No logs to verify'];
        }

        $errors = [];

        foreach ($logs as $i => $log) {
            $expectedPrev = $i === 0 ? null : $logs[$i - 1]->hash;

            // 1) cek prev_hash
            if ($log->prev_hash !== $expectedPrev) {
                $errors[] = [
                    'log_id' => $log->id,
                    'type' => 'prev_hash_mismatch',
                    'expected' => $expectedPrev,
                    'found' => $log->prev_hash,
                ];
                // lanjut cek hash juga tetap boleh, tapi biasanya chain udah rusak
            }

            // 2) cek hash beneran (recompute)
            $dataForHash = [
                'application_id' => $log->application_id,
                'log_type'       => $log->log_type,
                'payload'        => $log->payload,
                'ip_address'     => $log->ip_address ?? null,
                'user_agent'     => $log->user_agent ?? null,
                'created_at'     => optional($log->created_at)->toISOString(),
            ];

            $recomputed = $this->generateHash($dataForHash, $log->prev_hash);

            if (!hash_equals($log->hash, $recomputed)) {
                $errors[] = [
                    'log_id' => $log->id,
                    'type' => 'hash_mismatch',
                    'expected' => $recomputed,
                    'found' => $log->hash,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Hash chain valid' : 'Hash chain broken',
            'errors' => $errors,
            'total_checked' => $logs->count(),
        ];
    }
}
