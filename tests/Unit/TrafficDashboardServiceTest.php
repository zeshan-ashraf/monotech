<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\ApiTrafficHelper;
use App\Services\Dashboard\ApiTrafficService;
use App\Services\Dashboard\TrafficDashboardService;
use Mockery;
use Tests\TestCase;

class TrafficDashboardServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_get_traffic_calculates_rates_from_aggregated_metrics(): void
    {
        $apiTraffic = Mockery::mock(ApiTrafficService::class);
        $apiTraffic->shouldReceive('aggregateWindowMetrics')
            ->once()
            ->with(null, 5)
            ->andReturn([
                ApiTrafficHelper::FIELD_INCOMING => 300,
                ApiTrafficHelper::FIELD_ACCEPTED => 250,
                ApiTrafficHelper::FIELD_REJECTED => 50,
                ApiTrafficHelper::FIELD_GATEWAY_CALLS => 200,
                ApiTrafficHelper::FIELD_COMPLETED => 180,
                ApiTrafficHelper::FIELD_SUCCESS => 150,
                ApiTrafficHelper::FIELD_FAILED => 20,
                ApiTrafficHelper::FIELD_PENDING => 10,
                ApiTrafficHelper::FIELD_GATEWAY_ERRORS => 5,
                ApiTrafficHelper::FIELD_APPLICATION_ERRORS => 30,
                ApiTrafficHelper::FIELD_INFRASTRUCTURE_ERRORS => 8,
                ApiTrafficHelper::FIELD_TIMEOUTS => 3,
                ApiTrafficHelper::FIELD_SLOW_REQUESTS => 4,
                ApiTrafficHelper::FIELD_VERY_SLOW_REQUESTS => 1,
                ApiTrafficHelper::FIELD_TOTAL_RESPONSE_TIME => 240000,
                ApiTrafficHelper::FIELD_RESPONSE_SAMPLES => 200,
                ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME => 5200,
            ]);

        $apiTraffic->shouldReceive('incomingSparkline')
            ->once()
            ->with(null, 5)
            ->andReturn([40, 50, 60, 70, 80]);

        $apiTraffic->shouldReceive('aggregateWindowMetrics')
            ->with(['payin'], 5)
            ->andReturn([ApiTrafficHelper::FIELD_INCOMING => 100, ApiTrafficHelper::FIELD_SUCCESS => 80, ApiTrafficHelper::FIELD_ACCEPTED => 90, ApiTrafficHelper::FIELD_REJECTED => 10, ApiTrafficHelper::FIELD_GATEWAY_CALLS => 0]);

        $apiTraffic->shouldReceive('aggregateWindowMetrics')
            ->with(['payout'], 5)
            ->andReturn([ApiTrafficHelper::FIELD_INCOMING => 0, ApiTrafficHelper::FIELD_SUCCESS => 0, ApiTrafficHelper::FIELD_ACCEPTED => 0, ApiTrafficHelper::FIELD_REJECTED => 0, ApiTrafficHelper::FIELD_GATEWAY_CALLS => 0]);

        $apiTraffic->shouldReceive('aggregateWindowMetrics')
            ->with(['payin_status'], 5)
            ->andReturn([ApiTrafficHelper::FIELD_INCOMING => 0, ApiTrafficHelper::FIELD_SUCCESS => 0, ApiTrafficHelper::FIELD_ACCEPTED => 0, ApiTrafficHelper::FIELD_REJECTED => 0, ApiTrafficHelper::FIELD_GATEWAY_CALLS => 0]);

        $apiTraffic->shouldReceive('aggregateWindowMetrics')
            ->with(['payout_status'], 5)
            ->andReturn([ApiTrafficHelper::FIELD_INCOMING => 0, ApiTrafficHelper::FIELD_SUCCESS => 0, ApiTrafficHelper::FIELD_ACCEPTED => 0, ApiTrafficHelper::FIELD_REJECTED => 0, ApiTrafficHelper::FIELD_GATEWAY_CALLS => 0]);

        $apiTraffic->shouldReceive('aggregateWindowMetrics')
            ->with(['dashboard'], 5)
            ->andReturn([ApiTrafficHelper::FIELD_INCOMING => 0, ApiTrafficHelper::FIELD_SUCCESS => 0, ApiTrafficHelper::FIELD_ACCEPTED => 0, ApiTrafficHelper::FIELD_REJECTED => 0, ApiTrafficHelper::FIELD_GATEWAY_CALLS => 0]);

        $apiTraffic->shouldReceive('aggregateWindowMetrics')
            ->with(['webhook'], 5)
            ->andReturn([ApiTrafficHelper::FIELD_INCOMING => 0, ApiTrafficHelper::FIELD_SUCCESS => 0, ApiTrafficHelper::FIELD_ACCEPTED => 0, ApiTrafficHelper::FIELD_REJECTED => 0, ApiTrafficHelper::FIELD_GATEWAY_CALLS => 0]);

        $apiTraffic->shouldReceive('aggregateWindowMetrics')
            ->with(['other'], 5)
            ->andReturn([ApiTrafficHelper::FIELD_INCOMING => 0, ApiTrafficHelper::FIELD_SUCCESS => 0, ApiTrafficHelper::FIELD_ACCEPTED => 0, ApiTrafficHelper::FIELD_REJECTED => 0, ApiTrafficHelper::FIELD_GATEWAY_CALLS => 0]);

        $service = new TrafficDashboardService($apiTraffic);
        $traffic = $service->getTraffic(5);

        $this->assertSame(5, $traffic['window_minutes']);
        $this->assertSame(300, $traffic['window_seconds']);
        $this->assertSame(300, $traffic['incoming_requests']);
        $this->assertSame(1.0, $traffic['tps']);
        $this->assertSame(60.0, $traffic['tpm']);
        $this->assertSame(50.0, $traffic['success_rate']);
        $this->assertSame(1200.0, $traffic['average_response_time']);
        $this->assertSame(5200, $traffic['maximum_response_time']);
    }
}
