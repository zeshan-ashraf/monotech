<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\ApiTrafficHelper;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiTrafficHelperTest extends TestCase
{
    public function test_classifies_payin_routes_by_path(): void
    {
        $request = Request::create('/api/v1/payment-checkout', 'POST');

        $this->assertSame('payin', ApiTrafficHelper::classifyRequest($request));
    }

    public function test_classifies_status_check_routes_before_payin_prefix(): void
    {
        $request = Request::create('/api/payin-status-check', 'POST');

        $this->assertSame('payin_status', ApiTrafficHelper::classifyRequest($request));
    }

    public function test_classifies_dashboard_routes(): void
    {
        $request = Request::create('/api/v1/get-dashboard-data', 'GET');

        $this->assertSame('dashboard', ApiTrafficHelper::classifyRequest($request));
    }

    public function test_classifies_webhook_routes(): void
    {
        $request = Request::create('/api/jazzcash/callback', 'POST');

        $this->assertSame('webhook', ApiTrafficHelper::classifyRequest($request));
    }

    public function test_unknown_routes_map_to_other(): void
    {
        $request = Request::create('/api/unknown-endpoint', 'GET');

        $this->assertSame('other', ApiTrafficHelper::classifyRequest($request));
    }

    public function test_builds_redis_key_format(): void
    {
        $minute = new \DateTimeImmutable('2026-06-28 14:30:00');

        $this->assertSame(
            'metrics:traffic:payin:202606281430',
            ApiTrafficHelper::buildRedisKey('payin', $minute)
        );
    }

    public function test_normalizes_window_minutes_to_allowed_values(): void
    {
        $this->assertSame(5, ApiTrafficHelper::normalizeWindowMinutes(5));
        $this->assertSame(5, ApiTrafficHelper::normalizeWindowMinutes(99));
    }
}
