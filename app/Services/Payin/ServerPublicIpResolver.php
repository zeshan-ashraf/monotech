<?php

namespace App\Services\Payin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ServerPublicIpResolver
{
    private const CACHE_KEY = 'payin_diagnostics.server_public_ip';

    private const CACHE_TTL_SECONDS = 86400;

    public static function resolve(): ?string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): ?string {
            $configuredIp = env('SERVER_PUBLIC_IP');
            if (is_string($configuredIp) && trim($configuredIp) !== '') {
                return trim($configuredIp);
            }

            try {
                $response = Http::timeout(5)->get('https://api.ipify.org?format=json');
                if ($response->successful()) {
                    $ip = $response->json('ip');
                    if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            } catch (\Throwable) {
                // Public IP lookup is best-effort and cached for 24 hours.
            }

            return null;
        });
    }
}
