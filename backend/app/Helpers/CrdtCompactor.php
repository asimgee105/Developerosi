<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class CrdtCompactor
{
    /**
     * Compact CRDT update history when size exceeds 5MB or ticket is completed.
     *
     * @param string $crdtUpdatesJson
     * @param bool $isCompleted
     * @return array
     */
    public static function compactDocument(string $crdtUpdatesJson, bool $isCompleted = false): array
    {
        $payloadSize = strlen($crdtUpdatesJson);
        $maxLimitBytes = 5 * 1024 * 1024; // 5MB limit
        $shouldTombstone = ($payloadSize > $maxLimitBytes) || $isCompleted;

        if (!$shouldTombstone) {
            return [
                'compacted' => false,
                'payload' => $crdtUpdatesJson,
                'original_size' => $payloadSize,
                'new_size' => $payloadSize,
            ];
        }

        // Parse Yjs update history list
        $updates = json_decode($crdtUpdatesJson, true);
        if (!is_array($updates)) {
            $updates = [];
        }

        // Server-Side Tombstoning: Flatten update frames history state statefully, Keeping only the final flat values
        $flattenedState = [];
        foreach ($updates as $update) {
            if (isset($update['key']) && isset($update['value'])) {
                $flattenedState[$update['key']] = $update['value'];
            }
        }

        // Format back as compressed single flat update frame representation
        $compactedUpdates = [];
        foreach ($flattenedState as $key => $val) {
            $compactedUpdates[] = [
                'key' => $key,
                'value' => $val,
                'tombstoned' => true,
                'timestamp' => time(),
            ];
        }

        $compactedJson = json_encode($compactedUpdates);

        Log::info("CRDT Compaction: Document compacted successfully. Original size: {$payloadSize} bytes, Compacted size: " . strlen($compactedJson) . " bytes.");

        return [
            'compacted' => true,
            'payload' => $compactedJson,
            'original_size' => $payloadSize,
            'new_size' => strlen($compactedJson),
        ];
    }
}
