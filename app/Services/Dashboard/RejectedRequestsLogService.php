<?php

namespace App\Services\Dashboard;

use App\Helpers\GatewayMetricHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Fallback rejected-request counts from rejected_requests.log when Redis metrics miss entries.
 */
class RejectedRequestsLogService
{
    private const LOG_PATH = 'logs/rejected_requests.log';

    /**
     * @return array<string, int>
     */
    public function countByGateway(?int $minutes = null): array
    {
        $minutes = $minutes ?? GatewayMetricHelper::aggregationWindowMinutes();
        $since = now()->subMinutes($minutes);
        $counts = array_fill_keys(GatewayMetricHelper::supportedGateways(), 0);

        foreach ($this->parseRecentEntries($since) as $entry) {
            $gateway = $this->extractGateway($entry);

            if ($gateway !== null) {
                $counts[$gateway] = ($counts[$gateway] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @return list<string>
     */
    private function parseRecentEntries(Carbon $since): array
    {
        $path = storage_path(self::LOG_PATH);

        if (! File::exists($path)) {
            return [];
        }

        $entries = [];
        $currentEntry = null;
        $currentTimestamp = null;

        foreach (File::lines($path) as $line) {
            if (preg_match('/^\[([^\]]+)\]/', $line, $matches)) {
                if ($currentEntry !== null && $currentTimestamp instanceof Carbon && $currentTimestamp->gte($since)) {
                    $entries[] = $currentEntry;
                }

                $currentTimestamp = Carbon::parse($matches[1]);
                $currentEntry = $line;

                continue;
            }

            if ($currentEntry !== null) {
                $currentEntry .= PHP_EOL . $line;
            }
        }

        if ($currentEntry !== null && $currentTimestamp instanceof Carbon && $currentTimestamp->gte($since)) {
            $entries[] = $currentEntry;
        }

        return $entries;
    }

    private function extractGateway(string $entry): ?string
    {
        if (preg_match('/"payment_method"\s*:\s*"([^"]+)"/', $entry, $matches)) {
            $gateway = GatewayMetricHelper::normalizeGateway($matches[1]);

            if (GatewayMetricHelper::isSupportedGateway($gateway)) {
                return $gateway;
            }
        }

        if (preg_match('/"txn_type"\s*:\s*"([^"]+)"/', $entry, $matches)) {
            $gateway = GatewayMetricHelper::normalizeGateway($matches[1]);

            if (GatewayMetricHelper::isSupportedGateway($gateway)) {
                return $gateway;
            }
        }

        return null;
    }
}
