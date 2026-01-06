<?php

namespace App\Services;

use App\Models\UnifiedLog;

class HashChainService
{
    /**
     *  normalize payload agar hashing konsisten
     * - remove null / empty string
     * - recursive sort (ksort)
     */
    public function normalizeArray(array &$array): void
    {
        foreach ($array as $k => $v) {
            // remove null / empty string
            if ($v === null || $v === '') {
                unset($array[$k]);
                continue;
            }

            if (is_array($v)) {
                $this->normalizeArray($v);

                // jika kosong setelah normalize, hapus
                if ($v === []) {
                    unset($array[$k]);
                    continue;
                }

                $array[$k] = $v;
            }
        }

        ksort($array);
    }

    /**
     *  generate hash (HMAC SHA256)
     */
    public function generateHash(
        string $applicationId,
        int $seq,
        string $logType,
        array $payload,
        string $prevHash
    ): string {

        $this->normalizeArray($payload);

        $raw = implode('|', [
            $applicationId,
            $seq,
            strtoupper($logType),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $prevHash,
        ]);

        $secret = config('app.log_hash_key');

        return hash_hmac('sha256', $raw, $secret);
    }

    /**
     *  Verify full chain per application
     *
     * deteksi:
     * - deleted row (seq gap)
     * - prev_hash mismatch
     * - hash mismatch (payload diubah)
     *
     *  tambahan:
     * - tampilkan UUID log_id
     * - tampilkan created_at supaya mudah dicari
     */
    public function verifyChainByApplication(string $applicationId): array
    {
        $logs = UnifiedLog::where('application_id', $applicationId)
            ->orderBy('seq')
            ->get([
                'id',
                'seq',
                'log_type',
                'payload',
                'hash',
                'prev_hash',
                'created_at',
            ]);

        if ($logs->isEmpty()) {
            return [
                'valid' => true,
                'message' => 'No logs to verify',
                'errors' => [],
                'total_checked' => 0,
            ];
        }

        $errors = [];
        $prev = str_repeat('0', 64);
        $expectedSeq = 1;

        foreach ($logs as $log) {

            $prevHash = $log->prev_hash ?: str_repeat('0', 64);

            //  detect seq gap (row delete)
            if ((int)$log->seq !== $expectedSeq) {
                $errors[] = [
                    'seq' => (int)$log->seq,
                    'type' => 'seq_gap_or_delete_detected',
                    'missing_from' => $expectedSeq,
                    'missing_to' => ((int)$log->seq - 1),

                    'log_id' => (string)$log->id,
                    'log_type' => (string)$log->log_type,
                    'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
                ];

                $expectedSeq = (int)$log->seq;
            }

            //  prev_hash mismatch
            if ($prevHash !== $prev) {
                $errors[] = [
                    'seq' => (int)$log->seq,
                    'type' => 'prev_hash_mismatch',
                    'expected' => $prev,
                    'found' => $prevHash,

                    'log_id' => (string)$log->id,
                    'log_type' => (string)$log->log_type,
                    'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
                ];
            }

            //  recompute hash
            $payload = $log->payload ?? [];
            $this->normalizeArray($payload);

            $calc = $this->generateHash(
                $applicationId,
                (int)$log->seq,
                (string)$log->log_type,
                $payload,
                (string)$prevHash
            );

            if (!hash_equals($calc, $log->hash)) {
                $errors[] = [
                    'seq' => (int)$log->seq,
                    'type' => 'hash_mismatch',
                    'expected' => $calc,
                    'found' => $log->hash,

                    'log_id' => (string)$log->id,
                    'log_type' => (string)$log->log_type,
                    'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),

                    'payload_preview' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }

            $prev = $log->hash;
            $expectedSeq++;
        }


        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Hash chain valid' : 'Hash chain broken',
            'errors' => $errors,
            'total_checked' => $logs->count(),

            //  info broken pertama supaya gampang highlight
            'broken_at_seq' => !empty($errors) ? ($errors[0]['seq'] ?? null) : null,
            'broken_log_id' => !empty($errors) ? ($errors[0]['log_id'] ?? null) : null,
        ];
    }

    /**
     *  Verify single log security
     */
    public function verifySingleLog(UnifiedLog $log): array
    {
        $prevLog = UnifiedLog::where('application_id', $log->application_id)
            ->where('seq', $log->seq - 1)
            ->first();

        $expectedPrevHash = $prevLog ? $prevLog->hash : str_repeat('0', 64);

        $payload = $log->payload ?? [];
        $this->normalizeArray($payload);

        $calc = $this->generateHash(
            (string)$log->application_id,
            (int)$log->seq,
            (string)$log->log_type,
            $payload,
            (string)$log->prev_hash
        );

        return [
            'valid' => ($log->prev_hash === $expectedPrevHash) && hash_equals($calc, $log->hash),

            'prev_ok' => ($log->prev_hash === $expectedPrevHash),
            'hash_ok' => hash_equals($calc, $log->hash),

            //  tambahan supaya gampang debug
            'log_id' => (string)$log->id,
            'seq' => (int)$log->seq,
            'log_type' => (string)$log->log_type,
            'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),

            'expected_prev_hash' => $expectedPrevHash,
            'recomputed_hash' => $calc,
            'stored_hash' => $log->hash,
        ];
    }
}
