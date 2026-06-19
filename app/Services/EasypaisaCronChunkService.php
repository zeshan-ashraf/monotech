<?php

namespace App\Services;

use App\Models\ScheduleSetting;
use Illuminate\Support\Facades\Log;

class EasypaisaCronChunkService
{
    public function getActiveScheduleType(): ?string
    {
        return ScheduleSetting::query()
            ->where('txns_type', 'easypaisa')
            ->where('value', 1)
            ->value('type');
    }

    public function getChunk(string $cronType): int
    {
        $scheduleType = $this->getActiveScheduleType();
        $config = config('easypaisa_cron', []);

        if ($scheduleType && isset($config['chunk_by_schedule'][$scheduleType][$cronType])) {
            return (int) $config['chunk_by_schedule'][$scheduleType][$cronType];
        }

        $default = (int) ($config['default_chunk'][$cronType] ?? 50);
        $max = (int) ($config['max_chunk'] ?? 400);

        return min($default, $max);
    }

    public function logRunContext(string $cron, int $chunk, int $processed): void
    {
        Log::channel('payin')->info('Easypaisa cron chunk run', [
            'cron' => $cron,
            'schedule_type' => $this->getActiveScheduleType(),
            'chunk_limit' => $chunk,
            'processed' => $processed,
        ]);
    }
}
