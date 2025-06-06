<?php

declare(strict_types=1);

namespace App\Domains\PosMismatch\Services;

use Illuminate\Support\Facades\Log;

class PosMismatchService
{
    public function logMismatchEntries(
        string $infoMessage,
        int $recordId,
        array $messages,
        ?string $recordOfflineId
    ): void {
        Log::channel(config('logging.pos_mismatches_log_channel'))->info($infoMessage, [
            'record_id' => $recordId,
            'offline_id' => $recordOfflineId,
            'mismatch_messages' => $messages,
            'app_url' => config('app.url'),
        ]);
    }
}
