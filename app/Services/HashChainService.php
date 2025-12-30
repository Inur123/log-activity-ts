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
        //  pastikan urutan data konsisten
        $this->ksortRecursive($data);

        $payload = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_PRESERVE_ZERO_FRACTION
        ) . ($previousHash ?? '');

        return hash('sha256', $payload);
    }

    /**
     *  prev hash per application_id
     */
    public function getPreviousHash(?string $applicationId = null): ?string
    {
        $q = UnifiedLog::query()
            ->latest('created_at')
            ->latest('id'); //  deterministic kalau created_at sama

        if ($applicationId) {
            $q->where('application_id', $applicationId);
        }

        return $q->value('hash');
    }

    /**
     *  verifikasi chain (kalau ada yang edit manual DB -> ketahuan)
     */
    public function verifyChain(?string $applicationId = null): array
    {
        $q = UnifiedLog::query()
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc'); //  deterministic

        if ($applicationId) {
            $q->where('application_id', $applicationId);
        }

        $logs = $q->get([
            'id','application_id','log_type','payload','hash','prev_hash',
            'ip_address','user_agent','created_at'
        ]);

        if ($logs->isEmpty()) {
            return ['valid' => true, 'message' => 'No logs to verify'];
        }

        $errors = [];

        foreach ($logs as $i => $log) {
            $expectedPrev = $i === 0 ? null : $logs[$i - 1]->hash;

            if ($log->prev_hash !== $expectedPrev) {
                $errors[] = [
                    'log_id'    => (string) $log->id,
                    'type'      => 'prev_hash_mismatch',
                    'expected'  => $expectedPrev,
                    'found'     => $log->prev_hash,
                ];
            }

            $dataForHash = [
                'application_id' => (string) $log->application_id,
                'log_type'       => (string) $log->log_type,
                'payload'        => $log->payload,
                'ip_address'     => $log->ip_address,
                'user_agent'     => $log->user_agent,
                'created_at'     => optional($log->created_at)->toISOString(), //  harus sama seperti saat insert
            ];

            $recomputed = $this->generateHash($dataForHash, $log->prev_hash);

            if (!hash_equals($log->hash, $recomputed)) {
                $errors[] = [
                    'log_id'    => (string) $log->id,
                    'type'      => 'hash_mismatch',
                    'expected'  => $recomputed,
                    'found'     => $log->hash,
                ];
            }
        }

        return [
            'valid'          => empty($errors),
            'message'        => empty($errors) ? 'Hash chain valid' : 'Hash chain broken',
            'errors'         => $errors,
            'total_checked'  => $logs->count(),
        ];
    }
}
