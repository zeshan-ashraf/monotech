<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\GatewayMetricHelper;
use App\Models\Transaction;
use Carbon\Carbon;

/**
 * Builds OPS dashboard payment overview payloads from Redis gateway metrics.
 */
class PaymentDashboardService
{
    public function __construct(
        private readonly GatewayMetricService $gatewayMetricService
    ) {
    }

    /**
     * @return array{
     *     payin_success: int,
     *     payin_pending: int,
     *     payin_failed: int,
     *     payin_rejected: int,
     *     total_requests: int,
     *     success_rate: float,
     *     average_response_time: float,
     *     maximum_response_time: int,
     *     gateway_errors: int,
     *     application_errors: int,
     *     infrastructure_errors: int,
     *     timeouts: int,
     *     slow_requests: int,
     *     very_slow_requests: int
     * }
     */
    public function getMetrics(?array $gateways = null): array
    {
        $totals = $this->gatewayMetricService->aggregateWindowMetrics($gateways);
        $totalRequests = (int) ($totals[GatewayMetricHelper::FIELD_REQUESTS] ?? 0);
        $payinSuccess = (int) ($totals[GatewayMetricHelper::FIELD_SUCCESS] ?? 0);
        $responseSamples = (int) ($totals[GatewayMetricHelper::FIELD_RESPONSE_SAMPLES] ?? 0);
        $totalResponseTime = (int) ($totals[GatewayMetricHelper::FIELD_TOTAL_RESPONSE_TIME] ?? 0);

        return [
            'payin_success' => $payinSuccess,
            'payin_pending' => (int) ($totals[GatewayMetricHelper::FIELD_PENDING] ?? 0),
            'payin_failed' => (int) ($totals[GatewayMetricHelper::FIELD_FAILED] ?? 0),
            'payin_rejected' => (int) ($totals[GatewayMetricHelper::FIELD_REJECTED] ?? 0),
            'total_requests' => $totalRequests,
            'success_rate' => $this->calculateSuccessRate($payinSuccess, $totalRequests),
            'average_response_time' => $responseSamples > 0
                ? round($totalResponseTime / $responseSamples, 2)
                : 0.0,
            'maximum_response_time' => (int) ($totals[GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME] ?? 0),
            'gateway_errors' => (int) ($totals[GatewayMetricHelper::FIELD_GATEWAY_ERRORS] ?? 0),
            'application_errors' => (int) ($totals[GatewayMetricHelper::FIELD_APPLICATION_ERRORS] ?? 0),
            'infrastructure_errors' => (int) ($totals[GatewayMetricHelper::FIELD_INFRASTRUCTURE_ERRORS] ?? 0),
            'timeouts' => (int) ($totals[GatewayMetricHelper::FIELD_TIMEOUTS] ?? 0),
            'slow_requests' => (int) ($totals[GatewayMetricHelper::FIELD_SLOW_REQUESTS] ?? 0),
            'very_slow_requests' => (int) ($totals[GatewayMetricHelper::FIELD_VERY_SLOW_REQUESTS] ?? 0),
        ];
    }

    /**
     * Payment overview cards for the existing OPS dashboard Blade component.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paymentsOverview(?array $gateways = null): array
    {
        $metrics = $this->getMetrics($gateways);
        $sparklines = $this->gatewayMetricService->sparklineSeries($gateways);

        return [
            [
                'key' => 'success',
                'label' => 'PayIn Success',
                'value' => $metrics['payin_success'],
                'color' => 'success',
                'sparkline' => $sparklines['success'],
            ],
            [
                'key' => 'pending',
                'label' => 'PayIn Pending',
                'value' => $metrics['payin_pending'],
                'color' => 'warning',
                'sparkline' => $sparklines['pending'],
            ],
            [
                'key' => 'failed',
                'label' => 'PayIn Failed',
                'value' => $metrics['payin_failed'],
                'color' => 'danger',
                'sparkline' => $sparklines['failed'],
            ],
            [
                'key' => 'rejected',
                'label' => 'Rejected / Block',
                'value' => $metrics['payin_rejected'],
                'color' => 'info',
                'sparkline' => $sparklines['rejected'],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function paymentResponseStats(?array $gateways = null): array
    {
        $metrics = $this->getMetrics($gateways);

        return [
            'avg' => $metrics['average_response_time'] > 0
                ? GatewayMetricHelper::formatDurationSeconds($metrics['average_response_time'])
                : '0.00 sec',
            'max' => $metrics['maximum_response_time'] > 0
                ? GatewayMetricHelper::formatDurationSeconds($metrics['maximum_response_time'])
                : '0.00 sec',
        ];
    }

    /**
     * Per-gateway payment sections for the OPS dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function gatewaySections(): array
    {
        $sections = [];

        foreach (GatewayMetricHelper::supportedGateways() as $gateway) {
            $profile = GatewayMetricHelper::gatewayProfile($gateway);
            $sparklines = $this->gatewayMetricService->sparklineSeries([$gateway]);

            $sections[] = array_merge($profile, [
                'metrics' => $this->getMetrics([$gateway]),
                'cards' => $this->paymentsOverview([$gateway]),
                'payment_stats' => $this->paymentResponseStats([$gateway]),
                'sparklines' => $sparklines,
            ]);
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentMetricsPayload(?array $gateways = null): array
    {
        if ($gateways !== null) {
            return [
                'gateways' => [
                    array_merge(
                        GatewayMetricHelper::gatewayProfile($gateways[0]),
                        [
                            'metrics' => $this->getMetrics($gateways),
                            'cards' => $this->paymentsOverview($gateways),
                            'payment_stats' => $this->paymentResponseStats($gateways),
                            'sparklines' => $this->gatewayMetricService->sparklineSeries($gateways),
                        ]
                    ),
                ],
            ];
        }

        return [
            'gateways' => $this->gatewaySections(),
        ];
    }

    /**
     * Recent transactions for the OPS dashboard table (database-backed).
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentTransactions(int $limit = 6): array
    {
        return Transaction::query()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (Transaction $transaction): array {
                $status = strtolower((string) $transaction->status);
                $isRefund = in_array($status, ['refund', 'refunded', 'reversed'], true);

                return [
                    'id' => $transaction->txn_ref_no ?: $transaction->orderId,
                    'type' => $isRefund ? 'Refund' : 'PayIn',
                    'amount' => '₨ ' . number_format((float) $transaction->amount),
                    'status' => $this->normalizeTransactionStatus($status),
                    'time' => $transaction->created_at instanceof Carbon
                        ? $transaction->created_at->diffForHumans()
                        : '—',
                    'response_time' => in_array($status, ['pending'], true) ? '—' : '—',
                    'response_slow' => false,
                ];
            })
            ->values()
            ->all();
    }

    private function calculateSuccessRate(int $successCount, int $totalRequests): float
    {
        if ($totalRequests <= 0) {
            return 0.0;
        }

        return round(($successCount / $totalRequests) * 100, 2);
    }

    private function normalizeTransactionStatus(string $status): string
    {
        return match ($status) {
            'success', 'completed' => 'success',
            'pending' => 'pending',
            'fail', 'failed', 'error' => 'failed',
            'refund', 'refunded', 'reversed' => 'success',
            default => $status !== '' ? $status : 'pending',
        };
    }
}
